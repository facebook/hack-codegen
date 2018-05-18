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
 * Generate code for a type or newtype definition. Please don't use this class
 * directly; instead use the functions codegen_type or codegen_newtype. E.g.:
 *
 * codegen_type('Point')
 *   ->setType('(int, int)');
 */
final class CodegenType implements ICodeBuilderRenderer {

  use HackBuilderRenderer;

  private ?string $type;
  private ?ICodeBuilderRenderer $codegenShape;
  private string $keyword = 'type';

  public function __construct(
    protected IHackCodegenConfig $config,
    private string $name,
  ) {
  }

  public function setType(string $type): this {
    invariant(
      $this->codegenShape === null,
      "You can't set both the type and the shape.",
    );
    $this->type = $type;
    return $this;
  }

  public function setShape(mixed $codegen_shape): this {
    invariant(
      $this->type === null,
      "You can't set both the type and the shape.",
    );
    invariant(
      $codegen_shape instanceof ICodeBuilderRenderer && ($codegen_shape instanceof CodegenShape_FUTURE || $codegen_shape instanceof CodegenShape),
      "You must provide either CodegenShape_FUTURE or CodegenShape",
    );

    $this->codegenShape = $codegen_shape;
    return $this;
  }

  public function newType(): this {
    $this->keyword = 'newtype';
    return $this;
  }
  public function appendToBuilder(HackBuilder $builder): HackBuilder {
    invariant(
      $this->type !== null || $this->codegenShape !== null,
      "You need to set either the type or the shape",
    );
    $builder->addf('%s %s = ', $this->keyword, $this->name);
    if ($this->type !== null) {
      return $builder->add($this->type)->closeStatement();
    }
    invariant(
      $this->codegenShape !== null,
      "Somehow the type and the shape were null!",
    );
    return $builder->addRenderer($this->codegenShape)->closeStatement();
  }
}
