<?hh // strict
/**
 * Copyright (c) 2015-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the same directory.
 */

namespace Facebook\HackCodegen;

/**
 * Similar to SignedSource, but it uses a different header to indicate that the
 * file is partially generated
 */
final class PartiallyGeneratedSignedSource extends SignedSourceBase {

  protected static function preprocess(string $file_data): string {
    return (new PartiallyGeneratedCode($file_data))->extractGeneratedCode();
  }

  protected static function getTokenName(): string {
    return 'partially-generated';
  }

  /**
   * Get the text for a doc block that can be used for a partially
   * generated file.
   * If a comment is set, it will be included in the doc block.
   */
  public static function getDocBlock(?string $comment = null): string {
    $comment = \HH\Lib\Str\is_empty($comment) ? null : $comment."\n\n";
    return "This file is partially generated. ".
      "Only make modifications between BEGIN MANUAL SECTION ".
      "and END MANUAL SECTION designators.\n\n".
      $comment.
      self::getSigningToken();
  }
}
