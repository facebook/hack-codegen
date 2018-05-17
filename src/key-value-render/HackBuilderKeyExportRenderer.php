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

final class HackBuilderKeyExportRenderer
  implements IHackBuilderKeyRenderer<arraykey> {
  final public function render(IHackCodegenConfig $_, arraykey $value): string {
    return _Private\normalized_var_export($value);
  }
}
