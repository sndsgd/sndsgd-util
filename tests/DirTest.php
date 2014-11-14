<?php

use \org\bovigo\vfs\vfsStream;
use \sndsgd\util\Dir;
use \sndsgd\util\Path;
use \sndsgd\util\Temp;


class DirTest extends PHPUnit_Framework_TestCase
{
   /**
    * @coversNothing
    */
   protected function setUp()
   {
      $this->root = vfsStream::setup('root');
      vfsStream::create([
         'test' => [
            'file1.txt' => 'contents...',
            'emptydir' => [
            ]
         ],
         'noreadwrite' => [],
         'empty' => [],
         'rmfilefail' => [
            'file.txt' => 'contents'
         ],

         # 
         'rmdirfail' => [
            'sub' => [],
            'file.txt' => 'contents'
         ]
      ]);

      chmod(vfsStream::url('root/noreadwrite'), 0700);
      $this->root->getChild('noreadwrite')
         ->chown(vfsStream::OWNER_ROOT)
         ->chgrp(vfsStream::GROUP_ROOT);

      chmod(vfsStream::url('root/rmfilefail/file.txt'), 0700);
      $this->root->getChild('rmfilefail')->getChild('file.txt')
         ->chown(vfsStream::OWNER_ROOT)
         ->chgrp(vfsStream::GROUP_ROOT);

      chmod(vfsStream::url('root/rmdirfail/sub'), 0700);
      $this->root->getChild('rmdirfail')->getChild('sub')
         ->chown(vfsStream::OWNER_ROOT)
         ->chgrp(vfsStream::GROUP_ROOT);
   }

   /**
    * @coversNothing
    */
   public function testPathTestConsts()
   {
      $test = Dir::READABLE;
      $expect = Path::EXISTS | Path::IS_DIR | Path::IS_READABLE;
      $this->assertEquals($test, $expect);

      $test = Dir::WRITABLE;
      $expect = Path::EXISTS | Path::IS_DIR | Path::IS_WRITABLE;
      $this->assertEquals($test, $expect);

      $test = Dir::READABLE_WRITABLE;
      $expect = Path::EXISTS | Path::IS_DIR | Path::IS_READABLE | Path::IS_WRITABLE;
   }

   /**
    * @covers \sndsgd\util\Dir::isReadable
    */
   public function testIsReadable()
   {
      $tests = [
         [vfsStream::url('root/test'), true],
         [vfsStream::url('root/noreadwrite'), false],
         [vfsStream::url('root/does/not/exist'), false],
      ];

      foreach ($tests as list($path, $isReadable)) {
         $result = (Dir::isReadable($path) === true);
         $this->assertEquals($isReadable, $result);
      }
   }

   /**
    * @covers \sndsgd\util\Dir::isWritable
    */
   public function testIsWritable()
   {
      $tests = [
         [vfsStream::url('root/test'), true],
         [vfsStream::url('root/noreadwrite'), false],
         [vfsStream::url('root/noreadwrite/does/not/exist'), false],
         [vfsStream::url('root/test/does/not/exist'), true],
      ];

      foreach ($tests as list($test, $expect)) {
         $result = (Dir::isWritable($test) === true);
         $this->assertEquals($expect, $result);
      }
   }

   /**
    * @covers \sndsgd\util\Dir::prepare
    */
   public function testPrepare()
   {
      $tests = [
         [vfsStream::url('root/test'), true],
         [vfsStream::url('root/noreadwrite'), false],
         [vfsStream::url('root/noreadwrite/does/not/exist'), false],
         [vfsStream::url('root/test/does/not/exist'), true],
      ];

      foreach ($tests as list($test, $expect)) {
         $result = (Dir::prepare($test) === true);
         $this->assertEquals($expect, $result);
      }
   }

   /**
    * @covers \sndsgd\util\Dir::isEmpty
    */
   public function testIsEmpty()
   {
      $tests = [
         [vfsStream::url('root/test/emptydir'), true],
         [vfsStream::url('root/test'), false]
      ];

      foreach ($tests as list($test, $expect)) {
         $result = (Dir::isEmpty($test) === true);
         $this->assertEquals($expect, $result);
      }
   }

   /**
    * @covers \sndsgd\util\Dir::isEmpty
    * @expectedException InvalidArgumentException
    */
   public function testIsEmptyException()
   {
      Dir::isEmpty(123);
   }

   /**
    * @covers \sndsgd\util\Dir::isEmpty
    * @expectedException InvalidArgumentException
    */
   public function testIsEmptyNonDirException()
   {
      Dir::isEmpty(vfsStream::url('root/noreadwrite'));
   }

   /**
    * @covers \sndsgd\util\Dir::copy
    */
   public function testCopy()
   {
      $source = Path::normalize(__DIR__);
      $dest = vfsStream::url('root/noreadwrite');
      $this->assertTrue(is_string(Dir::copy($source, $dest)));

      $dest = vfsStream::url('root/test');
      $this->assertTrue(is_string(Dir::copy($source, $dest)));

      $dest = vfsStream::url('root/empty');
      $this->assertTrue(Dir::copy($source, $dest));
   }

   /**
    * @covers \sndsgd\util\Dir::remove
    */
   public function testRemove()
   {
      $dir = vfsStream::url('root/test');
      $this->assertTrue(Dir::remove($dir));

      $dir = vfsStream::url('root/noreadwrite');
      $this->assertTrue(is_string(Dir::remove($dir)));

      # sub directory cannot be read or written
      $dir = vfsStream::url('root/rmdirfail');
      $this->assertTrue(is_string(Dir::remove($dir)));

      # child file cannot be deleted
      $dir = vfsStream::url('root/remove-fail');
      $this->assertTrue(is_string(Dir::remove($dir)));
   }
}

