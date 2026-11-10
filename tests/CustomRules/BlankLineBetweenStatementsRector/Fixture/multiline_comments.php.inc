<?php

function multilineComments(): void
{
    first(); /* Trailing comment
        continues here. */
    second();
    third(); // Existing spacing.

    fourth();
}
-----
<?php

function multilineComments(): void
{
    first(); /* Trailing comment
        continues here. */

    second();

    third(); // Existing spacing.

    fourth();
}
