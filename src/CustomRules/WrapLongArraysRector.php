<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

/** @deprecated Use WrapLongListsRector to wrap arrays, arguments and parameters together. */
final class WrapLongArraysRector extends WrapLongListsRector
{
    protected const LIST_KIND = 'arrays';
}
