<?php
/**
 * Comment fixtures.
 */

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

/**
 * Class CommentFixtures.
 */
class CommentFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    /**
     * Load data.
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     * @psalm-suppress PossiblyNullReference
     * @psalm-suppress UnusedClosureParam
     */
    public function loadData(): void
    {
        if (null === $this->manager || null === $this->faker) {
            return;
        }

        $this->createMany(200, 'comments', function (int $i) {
            $comment = new Comment();
            $comment->setTitle($this->faker->sentence);
            $comment->setCreatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            $comment->setUpdatedAt(
                \DateTimeImmutable::createFromMutable(
                    $this->faker->dateTimeBetween('-100 days', '-1 days')
                )
            );
            /** @var Task $task */
            $task = $this->getRandomReference('tasks');
            $comment->setTask($task);

            /** @var User $author */
            $author = $this->getRandomUserReference('users');
            $comment->setAuthor($author);

            return $comment;
        });

        $this->manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on.
     *
     * @return string[] of dependencies
     *
     * @psalm-return array{0: TaskFixtures::class, 1: TagFixtures::class, 2: UserFixtures::class}
     */
    public function getDependencies(): array
    {
        return [TaskFixtures::class, TagFixtures::class, UserFixtures::class];
    }

    /**
     * @return User
     */
    private function getRandomUserReference(): User
    {
        $users = $this->manager->getRepository(User::class)->findAll();
        $randomUser = $this->faker->randomElement($users);

        return $randomUser;
    }
}
