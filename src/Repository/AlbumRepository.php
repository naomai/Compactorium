<?php
namespace Naomai\Compactorium\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Naomai\Compactorium\Entity\Album;
use Naomai\Compactorium\Slugger;

class AlbumRepository extends EntityRepository {
    /**
     * Initializes the repository for the Album entity.
     *
     * @param EntityManagerInterface $em Doctrine Entity Manager
     */
    public function __construct(EntityManagerInterface $em) {
        parent::__construct($em, $em->getClassMetadata(Album::class));
    }

    /**
     * Finds an Album by its artist and title.
     *
     * @param string $artist Album artist.
     * @param string $title Album title.
     * @return Album|null Matching Album entity, or null when not found.
     */
    public function findOneByArtistAndTitle(string $artist, string $title): ?Album {
        $slug = Slugger::slugFromArtistAndAlbum($artist, $title);

        return $this->find($slug);
    }
}
