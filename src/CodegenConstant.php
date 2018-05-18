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

/**
 * Generate code for a constant that is not part of a class.
 *
 * @see IHackCodegenFactory::codegenConstant
 */
final class CodegenConstant implements ICodeBuilderRenderer {
  use HackBuilderRenderer;

  private ?string $comment;
  private ?string $type;
  private ?string $value = null;

  /** @selfdocumenting */
  public function __construct(
    protected IHackCodegenConfig $config,
    private string $name,
  ) {
  }

  /** @selfdocumenting */
  public function getName(): string {
    return $this->name;
  }

  /** @selfdocumenting */
  public function getType(): ?string {
    return $this->type;
  }

  /** @selfdocumenting */
  public function getValue(): mixed {
    return $this->value;
  }

  /** @selfdocumenting */
  public function setDocBlock(string $comment): this {
    $this->comment = $comment;
    return $this;
  }

  /** @selfdocumenting */
  public function setType(string $type): this {
    $this->type = $type;
    return $this;
  }

  /** Set the type of the constant using a %-placeholder format string */
  public function setTypef(SprintfFormatString $format, mixed ...$args): this {
    return $this->setType(\vsprintf($format, $args));
  }

  /**
   * Set the value of the constant using a renderer.
   *
   * @param $renderer a renderer for the value. In general, this should be
   *   created using `HackBuilderValues`
   */
  public function setValue<T>(
      T $value,
      IHackBuilderValueRenderer<T> $renderer,
  ): this {
    $this->value = $renderer->render($this->config, $value);
    return $this;
  }

  public function appendToBuilder(HackBuilder $builder): HackBuilder {
    $value = $this->value;
    invariant(
      $value !== null,
      'constants must have a value',
    );
    return $builder
      ->addDocBlock($this->comment)
      ->ensureNewLine()
      ->add('const ')
      ->addIf($this->type !== null, $this->type.' ')
      ->add($this->name)
      ->addIf($value !== null, ' = '.$value)
      ->addLine(';');
  }
}
