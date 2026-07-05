<?php
namespace App\Attributes;
use \Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RelationAttribute
{
    public string $relationType;
    public function __construct($relationType)
    {
        $this->relationType = $relationType;
    }

    public function getRelationType()
    {
        return $this->relationType;
    }
}