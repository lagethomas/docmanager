<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\TagModel;
use PHPUnit\Framework\TestCase;

final class TagModelTest extends TestCase
{
    private TagModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new TagModel();
    }

    public function testTagCreateFindAndResolve(): void
    {
        $tagName = 'tag_' . uniqid();
        $id = $this->model->create($tagName);
        $this->assertGreaterThan(0, $id);

        $found = $this->model->find($id);
        $this->assertNotNull($found);
        $this->assertSame($tagName, $found['name']);

        $byName = $this->model->findByName($tagName);
        $this->assertNotNull($byName);
        $this->assertSame($id, (int) $byName['id']);

        $resolvedIds = $this->model->resolve([$tagName, 'new_tag_' . uniqid()]);
        $this->assertCount(2, $resolvedIds);
        $this->assertSame($id, $resolvedIds[0]);

        // Cleanup
        foreach ($resolvedIds as $tagId) {
            $this->model->delete($tagId);
        }
    }
}
