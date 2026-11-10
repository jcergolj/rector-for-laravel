<?php

function comments(): void
{
    first(); // Keep with first.
    // Explain second.
    second(); /* Also stays with second. */
    third(); fourth();
    /** Explain fifth. */
    fifth();


    sixth();
    // Final comment stays beside the brace.
}
-----
<?php

function comments(): void
{
    first(); // Keep with first.

    // Explain second.
    second(); /* Also stays with second. */

    third();

    fourth();

    /** Explain fifth. */
    fifth();


    sixth();
    // Final comment stays beside the brace.
}
