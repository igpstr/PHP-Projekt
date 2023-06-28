<?php
/**
 * Category service interface.
 */

namespace App\Service;

use App\Entity\Category;

/**
 * Interface CategoryServiceInterface.
 */
interface CategoryServiceInterface
{
    /**
     * @param Category $category
     *
     * @return void
     */
    public function save(Category $category): void;

    /**
     * @param Category $category
     *
     * @return void
     */
    public function delete(Category $category): void;

    /**
     * Can Category be deleted?
     *
     * @param Category $category Category entity
     *
     * @return bool Result
     */
    public function canBeDeleted(Category $category): bool;
}
