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
 * Generate code for a member variable. Please don't use this class directly;
 * instead use the function codegen_member_var.  E.g.:
 *
 * codegen_member_var('foo')
 *  ->setProtected()
 *  ->setType('string')
 *  ->setInlineComment('Represent the foo of the bar")
 *  ->render();
 */
final class CodegenMemberVar implements ICodeBuilderRenderer {

  const string UNSET_VALUE = "<<CodegenMemberVar_value_not_set>>";

  use CodegenWithVisibility;
  use HackBuilderRenderer;

  private ?string $comment;
  private ?string $type;
  private string $value = self::UNSET_VALUE;
  private bool $isStatic = false;

  public function __construct(
    protected IHackCodegenConfig $config,
    private string $name,
  ) {
    // Private by default
    $this->setPrivate();
  }

  public function getName(): string {
    return $this->name;
  }

  public function getType(): ?string {
    return $this->type;
  }

  public function getValue(): mixed {
    return $this->value;
  }

  public function setInlineComment(string $comment): this {
    $this->comment = $comment;
    return $this;
  }

  public function setIsStatic(bool $value = true): this {
    $this->isStatic = $value;
    return $this;
  }

  /**
   * Set the type of the member var.  In Hack, if it's nullable
   * you should prepend the question mark, e.g. "?string".
   */
  public function setType(string $type): this {
    $this->type = $type;
    return $this;
  }

  public function setTypef(SprintfFormatString $format, mixed ...$args): this {
    return $this->setType(vsprintf($format, $args));
  }

  /**
   * Set the initial value for the variable.  You can pass numbers, strings,
   * arrays, etc, and it will generate the code to render those values.
   */
  public function setValue(mixed $value): this {
    $val_str = normalized_var_export($value);
    return $this->setLiteralValue($val_str);
  }

  /**
   * Set the value of the variable to exactly what is specified. This is useful
   * for example to set it to "vec []", or other things that setValue
   * can't do.
   */
  public function setLiteralValue(string $value): this {
    $this->value = $value;
    return $this;
  }

  public function appendToBuilder(HackBuilder $builder): HackBuilder {
    return $builder
      ->addInlineComment($this->comment)
      ->add($this->getVisibility().' ')
      ->addIf($this->isStatic, 'static ')
      ->addIf($this->type !== null, $this->type.' ')
      ->add('$'.$this->name)
      ->addIf($this->value != self::UNSET_VALUE, ' = '.$this->value)
      ->addLine(';');
  }

}
