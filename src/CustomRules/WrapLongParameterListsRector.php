<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

/** @deprecated Use WrapLongListsRector to wrap arrays, arguments and parameters together. */
final class WrapLongParameterListsRector extends WrapLongListsRector
{
    protected const LIST_KIND = 'parameters';
}
