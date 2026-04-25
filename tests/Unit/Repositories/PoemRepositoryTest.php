<?php
namespace Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use BM\Repositories\PoemRepository;

class PoemRepositoryTest extends TestCase
{
    private PoemRepository $repository;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PoemRepository();
    }
    
    public function testFindReturnsArray(): void
    {
        $poem = $this->repository->find(1);
        $this->assertIsArray($poem);
        $this->assertArrayHasKey('id', $poem);
    }
    
    public function testFindByPoetReturnsArray(): void
    {
        $poems = $this->repository->findByPoet(1);
        $this->assertIsArray($poems);
    }
    
    public function testSearchByNameReturnsResults(): void
    {
        $results = $this->repository->searchByName('про');
        $this->assertIsArray($results);
    }
    
    public function testGetPoemTextReturnsString(): void
    {
        $text = $this->repository->getPoemText(1);
        $this->assertIsString($text);
    }
}
