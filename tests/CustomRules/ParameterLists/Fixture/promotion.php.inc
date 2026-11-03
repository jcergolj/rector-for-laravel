<?php

class TeamReportApiRequestBuilder
{
    public function __construct(public readonly Team $team, public readonly array $pages, public readonly ?User $user = null, public readonly array $metadata = []) {}
}
-----
<?php

class TeamReportApiRequestBuilder
{
    public function __construct(
        public readonly Team $team,
        public readonly array $pages,
        public readonly ?User $user = null,
        public readonly array $metadata = [],
    ) {}
}
