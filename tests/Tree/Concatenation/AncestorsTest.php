<?php

namespace Staudenmeir\LaravelAdjacencyList\Tests\Tree\Concatenation;

use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\Role;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\Models\User;
use Staudenmeir\LaravelAdjacencyList\Tests\Tree\TestCase;

class AncestorsTest extends TestCase
{
    public function testLazyLoading(): void
    {
        $posts = User::find(8)->ancestorPosts()->orderBy('id')->get();

        $this->assertEquals([10, 20, 50], $posts->pluck('id')->all());
    }

    public function testLazyLoadingAndSelf(): void
    {
        $posts = User::find(8)->ancestorAndSelfPosts()->orderBy('id')->get();

        $this->assertEquals([10, 20, 50, 80], $posts->pluck('id')->all());
    }

    public function testLazyLoadingWithoutParentKey(): void
    {
        $posts = (new User())->ancestorPosts()->get();

        $this->assertEmpty($posts);
    }

    public function testEagerLoading(): void
    {
        $users = User::with([
            'ancestorPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([], $users[0]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([10], $users[1]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50], $users[7]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([], $users[10]->ancestorPosts->pluck('id')->all());
    }

    public function testEagerLoadingAndSelf(): void
    {
        $users = User::with([
            'ancestorAndSelfPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertEquals([10], $users[0]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([10, 20], $users[1]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50, 80], $users[7]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([100, 110], $users[10]->ancestorAndSelfPosts->pluck('id')->all());
    }

    public function testEagerLoadingWithHasOneDeep(): void
    {
        $users = User::with([
            'ancestorPost' => fn (HasOneDeep $query) => $query->orderBy('id'),
        ])->orderBy('id')->get();

        $this->assertNull($users[0]->ancestorPost);
        $this->assertEquals(10, $users[1]->ancestorPost->id);
        $this->assertEquals(10, $users[7]->ancestorPost->id);
        $this->assertNull($users[10]->ancestorPost);
    }

    public function testLazyEagerLoading(): void
    {
        $users = User::orderBy('id')->get()->load([
            'ancestorPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([], $users[0]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([10], $users[1]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50], $users[7]->ancestorPosts->pluck('id')->all());
        $this->assertEquals([], $users[10]->ancestorPosts->pluck('id')->all());
    }

    public function testLazyEagerLoadingAndSelf(): void
    {
        $users = User::orderBy('id')->get()->load([
            'ancestorAndSelfPosts' => fn (HasManyDeep $query) => $query->orderBy('id'),
        ]);

        $this->assertEquals([10], $users[0]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([10, 20], $users[1]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([10, 20, 50, 80], $users[7]->ancestorAndSelfPosts->pluck('id')->all());
        $this->assertEquals([100, 110], $users[10]->ancestorAndSelfPosts->pluck('id')->all());
    }

    public function testExistenceQuery(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::find(1)->descendants()->has('ancestorPosts', '>', 1)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testExistenceQueryAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore', 'firebird'])) {
            $this->markTestSkipped();
        }

        $users = User::find(1)->descendants()->has('ancestorAndSelfPosts', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelation(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore'])) {
            $this->markTestSkipped();
        }

        $users = User::has('ancestorPosts', '>', 1)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testExistenceQueryForSelfRelationAndSelf(): void
    {
        if (in_array($this->connection, ['mariadb', 'sqlsrv', 'singlestore'])) {
            $this->markTestSkipped();
        }

        $users = User::has('ancestorAndSelfPosts', '>', 2)->get();

        $this->assertEquals([5, 6, 7, 8, 9], $users->pluck('id')->all());
    }

    public function testUnsupportedPosition(): void
    {
        $this->expectExceptionMessage('Ancestors can only be at the beginning of deep relationships at the moment.');

        Role::find(11)->userAncestors;
    }
}
