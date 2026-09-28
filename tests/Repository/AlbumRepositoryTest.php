<?php declare(strict_types=1);
namespace Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Repository\AlbumRepository;
use PHPUnit\Framework\TestCase;

final class AlbumRepositoryTest extends TestCase{
    public function testFindOneByArtistAndTitleReturnsAlbumOnHit() : void {
        $expected = new Album();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')
            ->with(Album::class)
            ->willReturn(new ClassMetadata(Album::class));
        $em->expects($this->once())
            ->method('find')
            ->with(Album::class, 'black-sabbath--vol-4')
            ->willReturn($expected);

        $repo = new AlbumRepository($em);

        $result = $repo->findOneByArtistAndTitle('Black Sabbath', 'Vol. 4');

        $this->assertSame($expected, $result);
    }

    public function testFindOneByArtistAndTitleReturnsNullOnMiss() : void {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')
            ->with(Album::class)
            ->willReturn(new ClassMetadata(Album::class));
        $em->expects($this->once())
            ->method('find')
            ->with(Album::class, 'some-artist--some-title')
            ->willReturn(null);

        $repo = new AlbumRepository($em);

        $this->assertNull($repo->findOneByArtistAndTitle('Some Artist', 'Some Title'));
    }

    public function testLookupUsesSlugifiedArtistAndTitle() : void {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')
            ->with(Album::class)
            ->willReturn(new ClassMetadata(Album::class));
        $em->expects($this->once())
            ->method('find')
            ->with(Album::class, 'ac-dc--back-in-black')
            ->willReturn(null);

        $repo = new AlbumRepository($em);

        $this->assertNull($repo->findOneByArtistAndTitle('AC/DC', 'Back In Black'));
    }
}