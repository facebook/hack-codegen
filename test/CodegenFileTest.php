<?hh // strict
/*
 *  Copyright (c) 2015-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackCodegen;

use type Facebook\HackCodegen\_Private\Filesystem;
use namespace HH\Lib\Str;

final class CodegenFileTest extends CodegenBaseTest {
  public function testAutogenerated(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setDocBlock('Completely autogenerated!')
      ->addClass(
        $cgf
          ->codegenClass('AllAutogenerated')
          ->addMethod(
            $cgf
              ->codegenMethod('getName')
              ->setReturnType('string')
              ->setBody('return $this->name;'),
          ),
      )
      ->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testGenerateTopLevelFunctions(): void {
    $cgf = $this->getCodegenFactory();
    $function =
      $cgf->codegenFunction('fun')->setReturnType('int')->setBody('return 0;');
    $code = $cgf->codegenFile('no_file')->addFunction($function)->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testPartiallyGenerated(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->addClass(
        $cgf
          ->codegenClass('PartiallyGenerated')
          ->addMethod($cgf->codegenMethod('getSomething')->setManualBody()),
      )
      ->addClass(
        $cgf
          ->codegenClass('PartiallyGeneratedLoader')
          ->setDocBlock('We can put many clases in one file!'),
      )
      ->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  private function saveAutogeneratedFile(?string $fname = null): string {
    $cgf = $this->getCodegenFactory();
    if ($fname === null) {
      $fname = Filesystem::createTemporaryFile('codegen', true);
    }

    $cgf
      ->codegenFile($fname)
      ->setDocBlock('Testing CodegenFile with autogenerated files')
      ->addClass(
        $cgf
          ->codegenClass('Demo')
          ->addMethod($cgf
            ->codegenMethod('getName')
            ->setBody('return "Codegen";')),
      )
      ->save();

    return $fname;
  }

  private function saveManuallyWrittenFile(?string $fname = null): string {
    if ($fname === null) {
      $fname = Filesystem::createTemporaryFile('codegen', true);
    }

    Filesystem::writeFileIfChanged(
      $fname,
      "<?php\n"."// Some handwritten code",
    );
    return $fname;
  }

  private function savePartiallyGeneratedFile(
    ?string $fname = null,
    bool $extra_method = false,
  ): string {
    $cgf = $this->getCodegenFactory();

    if ($fname === null) {
      $fname = Filesystem::createTemporaryFile('codegen', true);
    }

    $class = $cgf
      ->codegenClass('Demo')
      ->addMethod(
        $cgf
          ->codegenMethod('getName')
          ->setBody('// manual_section_here')
          ->setManualBody(),
      );

    if ($extra_method) {
      $class->addMethod($cgf->codegenMethod('extraMethod')->setManualBody());
    }

    $cgf
      ->codegenFile($fname)
      ->setDocBlock('Testing CodegenFile with partially generated files')
      ->addClass($class)
      ->save();

    return $fname;
  }

  public function testSaveAutogenerated(): void {
    $cgf = $this->getCodegenFactory();
    $fname = $this->saveAutogeneratedFile();
    expect_with_context(static::class, Filesystem::readFile($fname))->toBeUnchanged();
  }

  public function testClobberManuallyWrittenCode(): void {
    $cgf = $this->getCodegenFactory();
    $this->expectException(CodegenFileNoSignatureException::class);

    $fname = $this->saveManuallyWrittenFile();
    $this->saveAutogeneratedFile($fname);
  }

  public function testReSaveAutogenerated(): void {
    $cgf = $this->getCodegenFactory();
    $fname = $this->saveAutogeneratedFile();
    $content0 = Filesystem::readFile($fname);
    $this->saveAutogeneratedFile($fname);
    $content1 = Filesystem::readFile($fname);
    expect($content0)->toEqual($content1);
  }

  public function testSaveModifiedAutogenerated(): void {
    $cgf = $this->getCodegenFactory();
    $this->expectException(CodegenFileBadSignatureException::class);

    $fname = $this->saveAutogeneratedFile();
    $content = Filesystem::readFile($fname);
    Filesystem::writeFile($fname, $content.'.');
    $this->saveAutogeneratedFile($fname);
  }


  public function testSavePartiallyGenerated(): void {
    $cgf = $this->getCodegenFactory();
    $fname = $this->savePartiallyGeneratedFile();
    $content = Filesystem::readFile($fname);
    expect_with_context(static::class, $content)->toBeUnchanged();
    expect(
      PartiallyGeneratedSignedSource::hasValidSignature($content),
    )->toBeTrue();
  }

  public function testReSavePartiallyGenerated(): void {
    $cgf = $this->getCodegenFactory();
    $fname = $this->savePartiallyGeneratedFile();
    $content0 = Filesystem::readFile($fname);
    $this->savePartiallyGeneratedFile($fname);
    $content1 = Filesystem::readFile($fname);
    expect($content0)->toEqual($content1);
  }

  public function testSaveModifiedWrongPartiallyGenerated(): void {
    $cgf = $this->getCodegenFactory();
    $this->expectException(CodegenFileBadSignatureException::class);

    $fname = $this->savePartiallyGeneratedFile();
    $content = Filesystem::readFile($fname);
    Filesystem::writeFile($fname, $content.'.');
    $this->saveAutogeneratedFile($fname);
  }

  private function createAndModifyPartiallyGeneratedFile(): string {
    $fname = $this->savePartiallyGeneratedFile();
    $content = Filesystem::readFile($fname);

    $new_content =
      \str_replace('// manual_section_here', 'return $this->name;', $content);
    expect($content == $new_content)->toBeFalse(
      "The manual content wasn't replaced. Please fix the test setup!",
    );
    Filesystem::writeFile($fname, $new_content);
    return $fname;
  }

  /**
   * Test modifying a manual section and saving.
   */
  public function testSaveModifiedManualSectionPartiallyGenerated(): void {
    $cgf = $this->getCodegenFactory();
    $fname = $this->createAndModifyPartiallyGeneratedFile();
    $this->savePartiallyGeneratedFile($fname);
    $content = Filesystem::readFile($fname);
    expect(\strpos($content, 'this->name') !== false)->toBeTrue();
  }

  /**
   * Test modifying a manual section and changing the code generation so
   * that the generated part is different too.
   */
  public function testSaveModifyPartiallyGenerated(): void {
    $cgf = $this->getCodegenFactory();
    $fname = $this->createAndModifyPartiallyGeneratedFile();
    $this->savePartiallyGeneratedFile($fname, true);
    $content = Filesystem::readFile($fname);
    expect(\strpos($content, 'return $this->name;') !== false)->toBeTrue();
    expect(\strpos($content, 'function extraMethod()') !== false)->toBeTrue();
  }

  public function testNoSignature(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setIsSignedFile(false)
      ->setDocBlock('Completely autogenerated!')
      ->addClass(
        $cgf
          ->codegenClass('NoSignature')
          ->addMethod(
            $cgf
              ->codegenMethod('getName')
              ->setReturnType('string')
              ->setBody('return $this->name;'),
          ),
      )
      ->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testNamespace(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setNamespace('MyNamespace')
      ->useNamespace('Another\Space')
      ->useType('My\Space\Bar', 'bar')
      ->useFunction('My\Space\my_function', 'f')
      ->useConst('My\Space\MAX_RETRIES')
      ->addClass($cgf->codegenClass('Foo'))
      ->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testStrictFile(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->addClass($cgf->codegenClass('Foo'))
      ->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testPhpFile(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setFileType(CodegenFileType::PHP)
      ->addClass($cgf->codegenClass('Foo'))
      ->render();

    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testExecutable(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setFileType(CodegenFileType::HACK_PARTIAL)
      ->setShebangLine('#!/usr/bin/env hhvm')
      ->setPseudoMainHeader('require_once(\'vendor/autoload.php\');')
      ->addFunction(
        $cgf
          ->codegenFunction('main')
          ->setReturnType('void')
          ->setBody('print("Hello, world!\n");'),
      )
      ->setPseudoMainFooter('main();')
      ->render();
    expect_with_context(static::class, $code)->toBeUnchanged();
  }

  public function testNoShebangInStrict(): void {
    $this->expectException(
      /* HH_FIXME[2049] no hhi for invariantexception */
      \HH\InvariantException::class,
    );
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setFileType(CodegenFileType::HACK_STRICT)
      ->setShebangLine('#!/usr/bin/env hhvm')
      ->render();
  }

  public function testNoPseudoMainHeaderInStrict(): void {
    $this->expectException(
      /* HH_FIXME[2049] no hhi for invariantexception */
      \HH\InvariantException::class,
    );
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setFileType(CodegenFileType::HACK_STRICT)
      ->setPseudoMainHeader('exit();')
      ->render();
  }

  public function testNoPseudoMainFooterInStrict(): void {
    $this->expectException(
      /* HH_FIXME[2049] no hhi for invariantexception */
      \HH\InvariantException::class,
    );
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setFileType(CodegenFileType::HACK_STRICT)
      ->setPseudoMainFooter('exit();')
      ->render();
  }

  public function testFormattingFullyGeneratedFile(): void {
    $cgf = new HackCodegenFactory(
      (new HackCodegenConfig())
        ->withRootDir(__DIR__)
        ->withFormatter(new HackfmtFormatter())
    );

    $code = $cgf
      ->codegenFile('no_file')
      ->addFunction(
        $cgf->codegenFunction('my_func')
          ->addParameter(
            'string $'.\str_repeat('a', 60),
          )
          ->addParameter(
            'string $'.\str_repeat('b', 60),
          )
          ->setReturnType('(string, string)')
          ->setBody(
            $cgf->codegenHackBuilder()
              ->addReturnf(
                'tuple($%s, $%s)',
                \str_repeat('a', 60),
                \str_repeat('b', 60),
              )
              ->getCode()
          )
      )
      ->render();
    expect_with_context(static::class, $code)->toBeUnchanged();
    expect(
      SignedSourceBase::hasValidSignatureFromAnySigner($code)
    )->toBeTrue("bad signed source");
    expect(
      Str\ends_with($code, "\n")
    )->toBeTrue("Should end with newline");
    expect(
      Str\ends_with($code, "\n\n")
    )->toBeFalse("Should end with one newline, not multiple");
    expect_with_context(static::class, $code)->toBeUnchanged();
    expect(
      SignedSourceBase::hasValidSignatureFromAnySigner($code)
    )->toBeTrue('bad signed source');
    expect(Str\ends_with($code, "\n"))->toBeTrue("Should end with newline");
    expect(
      Str\ends_with($code, "\n\n")
    )->toBeFalse("Should end with one newline, not multiple");
    $lines = Str\split($code, "\n");
    expect(
      Str\starts_with($lines[8], " ")
    )->toBeTrue('use spaces instead of tabs');
  }

  public function testFormattingUnsignedFile(): void {
    $cgf = new HackCodegenFactory(
      (new HackCodegenConfig())
        ->withRootDir(__DIR__)
        ->withFormatter(new HackfmtFormatter())
    );

    $code = $cgf
      ->codegenFile('no_file')
      ->setIsSignedFile(false)
      ->addFunction(
        $cgf->codegenFunction('my_func')
          ->addParameter(
            'string $'.\str_repeat('a', 60),
          )
          ->addParameter(
            'string $'.\str_repeat('b', 60),
          )
          ->setReturnType('(string, string)')
          ->setBody(
            $cgf->codegenHackBuilder()
              ->addReturnf(
                'tuple($%s, $%s)',
                \str_repeat('a', 60),
                \str_repeat('b', 60),
              )
              ->getCode()
          )
      )
      ->render();
    expect_with_context(static::class, $code)->toBeUnchanged();
    expect(
      SignedSourceBase::hasValidSignatureFromAnySigner($code)
    )->toBeFalse('file should be unsigned, but has valid signature');
  }

  public function testFormattingPartiallyGeneratedFile(): void {
    $cgf = new HackCodegenFactory(
      (new HackCodegenConfig())
        ->withRootDir(__DIR__)
        ->withFormatter(new HackfmtFormatter())
    );

    $code = $cgf
      ->codegenFile('no_file')
      ->addFunction(
        $cgf->codegenFunction('my_func')
          ->addParameter(
            'string $'.\str_repeat('a', 60),
          )
          ->addParameter(
            'string $'.\str_repeat('b', 60),
          )
          ->setReturnType('(string, string)')
          ->setBody(
            $cgf->codegenHackBuilder()
              ->startManualSection('whut')
              ->endManualSection()
              ->addReturnf(
                'tuple($%s, $%s)',
                \str_repeat('a', 60),
                \str_repeat('b', 60),
              )
              ->getCode()
          )
      )
      ->render();
    expect_with_context(static::class, $code)->toBeUnchanged();
    expect(
      SignedSourceBase::hasValidSignatureFromAnySigner($code)
    )->toBeTrue('bad signed source');
  }

  public function testConstants(): void {
    $cgf = $this->getCodegenFactory();
    $code = $cgf
      ->codegenFile('no_file')
      ->setNamespace('Foo\\Bar')
      ->useNamespace('Herp\\Derp')
      ->addConstant(
        $cgf->codegenConstant('FOO')
          ->setType('string')
          ->setValue('bar', HackBuilderValues::export()),
      )
      ->addConstant(
        $cgf->codegenConstant('HERP')
          ->setDocBlock('doc comment')
          ->setType('string')
          ->setValue('derp', HackBuilderValues::export()),
      )
      ->render();
    expect_with_context(static::class, $code)->toBeUnchanged();
  }
}
