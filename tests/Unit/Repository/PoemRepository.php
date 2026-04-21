<?php
namespace Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use BM\Core\Repository\PoemRepository;

class PoemRepositoryTest extends TestCase
{
    private PoemRepository $repository;
    
    protected function setUp(): void
    {
        $this->repository = new PoemRepository();
    }
    
    public function testFindReturnsArray()
    {
        $poem = $this->repository->find(1);
        $this->assertIsArray($poem);
        $this->assertArrayHasKey('id', $poem);
        $this->assertArrayHasKey('name', $poem);
        $this->assertArrayHasKey('poem_text', $poem);
    }
    
    public function testFindByPoetReturnsArray()
    {
        $poems = $this->repository->findByPoet(1);
        $this->assertIsArray($poems);
    }
    
    public function testSearchByNameReturnsResults()
    {
        $results = $this->repository->searchByName('про');
        $this->assertIsArray($results);
    }
    
    public function testGetPoemTextReturnsString()
    {
        $text = $this->repository->getPoemText(1);
        $this->assertIsString($text);
        $this->assertNotEmpty($text);
    }
}