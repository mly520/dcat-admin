<?php

namespace Dcat\Admin3\Http\Forms;

use Dcat\Admin3\Admin;
use Dcat\Admin3\Contracts\LazyRenderable;
use Dcat\Admin3\Exception\RuntimeException;
use Dcat\Admin3\Traits\LazyWidget;
use Dcat\Admin3\Widgets\Form;

class InstallFromLocal extends Form implements LazyRenderable
{
    use LazyWidget;

    public function handle(array $input)
    {
        $file = $input['extension'];

        if (! $file) {
            return $this->response()->error('Invalid arguments.');
        }

        try {
            $path = $this->getFilePath($file);

            $manager = Admin::extension();

            $extensionName = $manager->extract($path, true);

            if (! $extensionName) {
                return $this->response()->error(trans('admin.invalid_extension_package'));
            }

            $manager
                ->load()
                ->updateManager()
                ->update($extensionName);

            return $this->response()
                ->success(implode('<br>', $manager->updateManager()->notes))
                ->refresh();
        } catch (\Throwable $e) {
            Admin::reportException($e);

            return $this->response()->error($e->getMessage());
        } finally {
            if (! empty($path)) {
                @unlink($path);
            }
        }
    }

    public function form()
    {
        $this->file('extension')
            ->required()
            ->disk($this->disk())
            ->accept('zip', 'application/zip')
            ->autoUpload();
    }

    protected function getFilePath($file)
    {
        $root = config("filesystems.disks.{$this->disk()}.root");

        if (! $root) {
            throw new RuntimeException(sprintf('Missing \'root\' for disk [%s].', $this->disk()));
        }

        return rtrim($root, '/').'/'.$file;
    }

    protected function disk()
    {
        return config('admin.extension.disk') ?: 'local';
    }
}
