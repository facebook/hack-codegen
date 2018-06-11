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

final class ExpectObj<T> extends \Facebook\FBExpect\ExpectObj<T> {

  public function __construct(private ImmVector<mixed> $vars, private string $called_class = '') {
    parent::__construct($vars);
    $this->called_class = $called_class;
  }

  public function toBeUnchanged(?string $token = null, string $msg = '', ...): void {
    $msg = \vsprintf($msg, \array_slice(\func_get_args(), 2));
    $class_name = $this->called_class;
    $path = CodegenExpectedFile::getPath($class_name);
    $expected = CodegenExpectedFile::parseFile($path);
    $token = $token === null ? CodegenExpectedFile::findToken() : $token;
    $value = (string) $this->vars->firstValue();
    if ($expected->contains($token) && $expected[$token] === $value) {
      return;
    }
    $new_expected = clone $expected;
    $new_expected[$token] = $value;
    CodegenExpectedFile::writeExpectedFile($path, $new_expected, $expected);

    $expected = CodegenExpectedFile::parseFile($path);
    invariant(
      $expected->contains($token) && $expected[$token] === $value,
      'New value not accepted by user',
    );
  }
}
