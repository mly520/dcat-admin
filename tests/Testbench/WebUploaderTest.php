<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Support\WebUploader;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 分片上传的临时目录由请求中的 _id 拼出,_id 必须不能跳出临时目录,
 * 否则任何后台用户都能在 storage 之外建目录、写入分片文件。
 */
class WebUploaderTest extends TestCase
{
    private function makeUploader(string $id): WebUploader
    {
        $request = Request::create('/upload', 'POST', [
            '_id' => $id,
            'chunk' => '0',
            'chunks' => '2',
            'upload_column' => 'avatar',
        ], [], [
            WebUploader::FILE_NAME => UploadedFile::fake()->create('a.txt', 1),
        ]);

        return new WebUploader($request);
    }

    public static function maliciousIds(): array
    {
        return [
            'parent traversal' => ['../../public/x'],
            'parent only' => ['..'],
            'current dir' => ['.'],
            'absolute path' => ['/tmp/x'],
            'backslash' => ['..\\x'],
        ];
    }

    /**
     * @dataProvider maliciousIds
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('maliciousIds')]
    public function test_rejects_id_escaping_temporary_directory(string $id): void
    {
        $uploader = $this->makeUploader($id);

        try {
            $uploader->getUploadedFile();
            $this->fail('非法 _id 应被拒绝');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    // 服务端生成的 Str::random() 以及前端兜底的「字段名(可含 .)+随机串」都必须仍可正常分片上传
    public function test_accepts_generated_ids(): void
    {
        foreach (['aB3dE5fG7hJ9kL1m', 'profile.avatarX9k2', 'webup-abc_123'] as $id) {
            $uploader = $this->makeUploader($id);

            // 2 片中的第 1 片:尚未完整,返回 false,分片落在临时目录内
            $this->assertFalse($uploader->getUploadedFile());
            $this->assertDirectoryExists($uploader->getTemporaryPath($id));

            app('files')->deleteDirectory($uploader->getTemporaryPath($id));
        }
    }
}
