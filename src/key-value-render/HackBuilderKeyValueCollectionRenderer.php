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

class
  HackBuilderKeyValueCollectionRenderer<
    Tk as arraykey,
    Tv,
    T as KeyedTraversable<Tk, Tv>,
  > implements IHackBuilderValueRenderer<T> {
  public function __construct(
    private classname<T> $containerName,
    private IHackBuilderKeyRenderer<Tk> $keyRenderer,
    private IHackBuilderValueRenderer<Tv> $valueRenderer,
  ) {
  }

  final public function render(IHackCodegenConfig $config, T $values): string {
    $key_renderer = $this->keyRenderer;
    $value_renderer = $this->valueRenderer;

    $builder = (new HackBuilder($config))
      ->add(_Private\strip_hh_prefix($this->containerName))
      ->openBrace();
    foreach ($values as $key => $value) {
      $builder->addWithSuggestedLineBreaksf(
        "%s =>\t%s,\n",
        $key_renderer->render($config, $key),
        $value_renderer->render($config, $value),
      );
    }
    return $builder->unindent()->add('}')->getCode();
  }
}
