<?php

namespace Tests\Unit;

use App\Models\Departement;
use App\Models\Region;
use Tests\TestCase;

class RegionRelationTest extends TestCase
{
    public function test_departments_relation_targets_departement_model(): void
    {
        $related = (new Region())->departments()->getRelated();

        $this->assertInstanceOf(Departement::class, $related);
    }
}
