<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Models\SettingsModel;
use PHPUnit\Framework\TestCase;

class TimbradoSettingsTest extends TestCase
{
    private SettingsModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SettingsModel();
    }

    public function testSetAndGetTimbradoSettings(): void
    {
        $this->model->set('timbrado_enabled', '1');
        $this->model->set('timbrado_filename', 'papel_timbrado_empresa.docx');

        $this->assertSame('1', $this->model->get('timbrado_enabled'));
        $this->assertSame('papel_timbrado_empresa.docx', $this->model->get('timbrado_filename'));
    }

    public function testAllAsMapContainsTimbradoKeys(): void
    {
        $this->model->set('timbrado_enabled', '0');
        $map = $this->model->allAsMap();

        $this->assertArrayHasKey('timbrado_enabled', $map);
        $this->assertSame('0', $map['timbrado_enabled']);
    }
}
