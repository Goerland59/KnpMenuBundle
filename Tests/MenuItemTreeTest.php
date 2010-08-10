<?php

namespace Bundle\MenuBundle\Tests;
use Bundle\MenuBundle\MenuItem;

class TestMenuItem extends MenuItem {}

class MenuItemTreeTest extends \PHPUnit_Framework_TestCase
{

    public function testSampleTreeIntegrity()
    {
        extract($this->getSampleTree());

        $this->assertEquals(2, count($menu));
        $this->assertEquals(3, count($menu['Parent 1']));
        $this->assertEquals(1, count($menu['Parent 2']));
        $this->assertEquals(1, count($menu['Parent 2']['Child 4']));
        $this->assertEquals('Grandchild 1', $menu['Parent 2']['Child 4']['Grandchild 1']->getName());
    }

    public function testChildrenHaveParentClass()
    {
        $class = 'Bundle\MenuBundle\Tests\TestMenuItem';
        extract($this->getSampleTree($class));

        $this->assertTrue($pt1 instanceof $class);
        $this->assertTrue($ch1 instanceof $class);
    } 

    public function testGetLevel()
    {
        extract($this->getSampleTree());
        $this->assertEquals(0, $menu->getLevel());
        $this->assertEquals(1, $pt1->getLevel());
        $this->assertEquals(1, $pt2->getLevel());
        $this->assertEquals(2, $ch4->getLevel());
        $this->assertEquals(3, $gc1->getLevel());
    }

    public function testGetRoot()
    {
        extract($this->getSampleTree());
        $this->assertEquals($menu, $menu->getRoot());
        $this->assertEquals($menu, $pt1->getRoot());
        $this->assertEquals($menu, $gc1->getRoot());
    }

    public function testIsRoot()
    {
        extract($this->getSampleTree());
        $this->assertTrue($menu->isRoot());
        $this->assertFalse($pt1->isRoot());
        $this->assertFalse($ch3->isRoot());
    }

    public function testGetParent()
    {
        extract($this->getSampleTree());
        $this->assertEquals(null, $menu->getParent());
        $this->assertEquals($menu, $pt1->getParent());
        $this->assertEquals($ch4, $gc1->getParent());
    }

    public function testMoveSampleMenuToNewRoot()
    {
        extract($this->getSampleTree());
        $newRoot = new TestMenuItem("newRoot");
        $newRoot->addChild($menu);

        $this->assertEquals(1, $menu->getLevel());
        $this->assertEquals(2, $pt1->getLevel());

        $this->assertEquals($newRoot, $menu->getRoot());
        $this->assertEquals($newRoot, $pt1->getRoot());
        $this->assertFalse($menu->isRoot());
        $this->assertTrue($newRoot->isRoot());
        $this->assertEquals($newRoot, $menu->getParent());
    }

    public function testIsFirst()
    {
        extract($this->getSampleTree());
        $this->assertTrue($pt1->isFirst());
        $this->assertFalse($pt2->isFirst());
        $this->assertTrue($ch4->isFirst());
    }

    public function testIsLast()
    {
        extract($this->getSampleTree());
        $this->assertFalse($pt1->isLast());
        $this->assertTrue($pt2->isLast());
        $this->assertTrue($ch4->isLast());
    }

    public function testArrayAccess()
    {
        extract($this->getSampleTree());

        $menu->addChild('Child Menu');
        $this->assertEquals('Child Menu', $menu['Child Menu']->getName());
        $this->assertEquals(null, $menu['Fake']);

        $menu['New Child'] = 'New Label';
        $this->assertEquals('Bundle\MenuBundle\MenuItem', get_class($menu['New Child']));
        $this->assertEquals('New Child', $menu['New Child']->getName());
        $this->assertEquals('New Label', $menu['New Child']->getLabel());

        unset($menu['New Child']);
        $this->assertEquals(null, $menu['New Child']);
    }

    public function testCountable()
    {
        extract($this->getSampleTree());
        $this->assertEquals(2, count($menu));

        $menu->addChild('New Child');
        $this->assertEquals(3, count($menu));

        unset($menu['New Child']);
        $this->assertEquals(2, count($menu));
    }

    public function testIterator()
    {
        extract($this->getSampleTree());
        $count = 0;
        foreach ($pt1 as $key => $value)
        {
            $count++;
            $this->assertEquals('Child '.$count, $key);
            $this->assertEquals('Child '.$count, $value->getLabel());
        }
    }

    public function testGetChildren()
    {
        extract($this->getSampleTree());
        $children = $ch4->getChildren();
        $this->assertEquals(1, count($children));
        $this->assertEquals($gc1->getName(), $children['Grandchild 1']->getName());
    }

    public function testGetFirstChild()
    {
        extract($this->getSampleTree());
        $this->assertEquals($pt1, $menu->getFirstChild());
        // test for bug in getFirstChild implementation (when internal array pointer is changed getFirstChild returns wrong child)
        foreach ($menu->getChildren() as $c);
        $this->assertEquals($pt1, $menu->getFirstChild());
    }

    public function testGetLastChild()
    {
        extract($this->getSampleTree());
        $this->assertEquals($pt2, $menu->getLastChild());
        // test for bug in getFirstChild implementation (when internal array pointer is changed getLastChild returns wrong child)
        foreach ($menu->getChildren() as $c);
        $this->assertEquals($pt2, $menu->getLastChild());
    }

    public function testAddChild()
    {
        extract($this->getSampleTree('Bundle\MenuBundle\Tests\TestMenuItem'));

        // a) Add a child (gc2) to ch4 via ->addChild().
        $gc2 = $ch4->addChild('gc2');
        $this->assertEquals(2, count($ch4->getChildren()));
        $this->assertEquals('Bundle\MenuBundle\Tests\TestMenuItem', get_class($gc2));

        // b) Add another child (temp) to ch4 via ->addChild(), but specify the class.
        $temp = $ch4->addChild('temp', null, array(), 'Bundle\MenuBundle\Tests\TestMenuItem');
        $this->assertEquals('Bundle\MenuBundle\Tests\TestMenuItem', get_class($temp));
        $ch4->removeChild($temp);
        
        // c) Add a child (gc3) to ch4 by passing an object to addChild().
        $gc3 = new TestMenuItem('gc3');
        $ch4->addChild($gc3);
        $this->assertEquals(3, count($ch4->getChildren()));
        
        // d) Try to add gc3 again, should throw an exception.
        try
        {
            $pt1->addChild($gc3);
            $this->assertTrue(false);
        }
        catch (\LogicException $e)
        {
            $this->assertTrue(true);
        }
    }

    public function testGetChild()
    {
        extract($this->getSampleTree());
        $this->assertEquals($gc1, $ch4->getChild('Grandchild 1'));
        $this->assertEquals('gc4', $ch4->getChild('gc4')->getName());
        $this->assertEquals(2, count($ch4));
        $this->assertEquals(null, $ch4->getChild('nonexistentchild', false));
    }

  //$t->info('  3.5 - Test ->removeChildren()');
  //$t->info('    a) ch4 now has 4 children (gc1, gc2, gc3, gc4). Remove gc4.');
    //$gc4 = $ch4['gc4']; // so we can try to re-add it later
    //$ch4->removeChild('gc4');
    //$t->is(count($ch4), 3, 'count(ch4) now returns only 3 children.');
    //$t->is($ch4->getChild('Grandchild 1')->isFirst(), true, '->isFirst() on gc1 correctly returns true.');
    //$t->is($ch4->getChild('gc3')->isLast(), true, '->isLast() on gc3 now returns true.');
    //$t->info('Now that gc4 has been removed, we can add it to another menu without an exception.');
    //$tmpMenu = new ioMenu();
    //try
    //{
      //$tmpMenu->addChild($gc4);
      //$t->pass('No exception thrown.');
    //}
    //catch (sfException $e)
    //{
      //$t->fail('Exception still thrown: ' . $e->getMessage());
    //}

  //$t->info('    b) ch4 now has 3 children (gc1, gc2, gc3). Remove gc2.');
    //$ch4->removeChild('gc2');
    //$t->is(count($ch4), 2, 'count(ch4) now returns only 2 children.');
    //$t->is($ch4->getChild('Grandchild 1')->isFirst(), true, '->isFirst() on gc1 correctly returns true.');
    //$t->is($ch4->getChild('gc3')->isLast(), true, '->isLast() on gc3 now returns true');
    //$t->is($gc1->getNum(), 0, '->getNum() on gc1 returns 0');
    //$t->is($ch4->getChild('gc3')->getNum(), 1, '->getNum() on gc3 returns 1');

  //$t->info('    c) ch4 now has 2 children (gc1, gc3). Remove gc3.');
    //$ch4->removeChild('gc3');
    //$t->is(count($ch4), 1, 'count(ch4) now returns only 1 child.');
    //$t->is($gc1->isFirst(), true, '->isFirst() on gc1 returns true.');
    //$t->is($gc1->isLast(), true, '->isLast() on gc1 returns true.');

  //$t->info('    d) try to remove a non-existent child.');
    //$ch4->removeChild('fake');
    //$t->is(count($ch4), 1, '->removeChildren() with a non-existent child does nothing');

  //$t->info('  3.5 - Test updating child id after rename');
  //$pt1->setName("Temp name");
  //$t->is($menu->getChild("Temp name", false), $pt1, "pt1 can be found under new name");
  //$t->is(array_keys($menu->getChildren()), array('Temp name', 'Parent 2'), 'The children are still ordered correctly after a rename.');

  //$pt1->setName("Parent 1");
  //$t->is($menu->getChild("Parent 1", false), $pt1, "pt1 can be found again under old name");

  //$t->info('    Trying renaming Parent 1 to Parent 2 (which already is used by sibling), should throw an exception.');
    //try
    //{
      //$pt1->setName("Parent 2");
      //$t->fail('Exception not thrown.');
    //}
    //catch (sfException $e)
    //{
      //$t->pass('Exception thrown');
    //}

    public function testGetSetCurrentUri()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $this->assertEquals(null, $menu->getCurrentUri());
        $menu->setCurrentUri('http://symfony-reloaded.org/');
        $this->assertEquals('http://symfony-reloaded.org/', $menu->getCurrentUri());
        $this->assertEquals('http://symfony-reloaded.org/', $menu['child']->getCurrentUri());
    }

    public function testChildrenCurrentUri()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $menu->setCurrentUri('http://symfony-reloaded.org/');
        $menu->addChild('test_child', 'http://php.net/');
        $this->assertEquals('http://symfony-reloaded.org/', $menu['test_child']->getCurrentUri());
    }

    public function testGetIsCurrentWhenCurrentUriIsNotSet()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $this->assertFalse($menu['child']->getIsCurrent());
    }

    public function testGetIsCurrentWhenCurrentUriIsSet()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $menu->setCurrentUri('http://www.symfony-reloaded.org');
        $this->assertTrue($menu['child']->getIsCurrent());
        $this->assertFalse($pt1->getIsCurrent());
    }

    public function testGetIsCurrentAncestor()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $menu->setCurrentUri('http://php.net');
        $pt1->setRoute('http://php.net');
        $this->assertFalse($pt1->getIsCurrentAncestor());
        $this->assertTrue($menu->getIsCurrentAncestor());
    }

    public function testDeepGetIsCurrentAncestor()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $menu->setCurrentUri('http://php.net');
        $gc1->setRoute('http://php.net');
        $this->assertFalse($pt1->getIsCurrentAncestor());
        $this->assertTrue($menu->getIsCurrentAncestor());
        $this->assertTrue($pt2->getIsCurrentAncestor());
        $this->assertTrue($ch4->getIsCurrentAncestor());
    }

    public function testGetUri()
    {
        extract($this->getSampleTreeWithExternalUrl());
        $this->assertEquals(null, $pt1->getUri());
        $this->assertEquals('http://www.symfony-reloaded.org', $menu['child']->getUri());
    }

    public function getSampleTreeWithExternalUrl($class = 'Bundle\MenuBundle\MenuItem')
    {
        $items = $this->getSampleTree($class);
        $items['menu']->addChild('child', 'http://www.symfony-reloaded.org');

        return $items;
    }

    /**
     * @return array the tree items
     */
    protected function getSampleTree($class = 'Bundle\MenuBundle\MenuItem')
    {
        $menu = new $class('Root li', null, array('class' => 'root'));
        $pt1 = $menu->getChild('Parent 1');
        $ch1 = $pt1->addChild('Child 1');
        $ch2 = $pt1->addChild('Child 2');

        // add the 3rd child via addChild with an object
        $ch3 = new $class('Child 3');
        $pt1->addChild($ch3);

        $pt2 = $menu->getChild('Parent 2');
        $ch4 = $pt2->addChild('Child 4');
        $gc1 = $ch4->addChild('Grandchild 1');

        $items = array(
            'menu'  => $menu,
            'pt1'   => $pt1,
            'pt2'   => $pt2,
            'ch1'   => $ch1,
            'ch2'   => $ch2,
            'ch3'   => $ch3,
            'ch4'   => $ch4,
            'gc1'   => $gc1,
        );

        return $items;
    }

    // prints a visual representation of our basic testing tree
    protected function printTestTree()
    {
        print('      Menu Structure   '."\n");
        print('               rt      '."\n");
        print('             /    \    '."\n");
        print('          pt1      pt2 '."\n");
        print('        /  | \      |  '."\n");
        print('      ch1 ch2 ch3  ch4 '."\n");
        print('                    |  '."\n");
        print('                   gc1 '."\n");
    }
}