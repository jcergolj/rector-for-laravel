<?php

declare(strict_types=1);

namespace Jcergolj\RectorForLaravel\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\PhpParser\Node\FileNode;
use Rector\PHPUnit\Enum\PHPUnitAttribute;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\StaticTypeMapper\ValueObject\Type\FullyQualifiedObjectType;
use RectorLaravel\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see PhpUnitTestDocblockToAttributeRectorTest
 */
class PhpUnitTestDocblockToAttributeRector extends AbstractRector
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
        private readonly PhpDocInfoFactory $phpDocInfoFactory,
        private readonly PhpDocTagRemover $phpDocTagRemover,
        private readonly DocBlockUpdater $docBlockUpdater,
        private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer,
    ) {}

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert PHPUnit @test docblocks to the #[Test] attribute',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
     */
    public function user_can_log_in(): void
    {
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    public function user_can_log_in(): void
    {
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param  ClassMethod  $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }

        if (! $this->testsNodeAnalyzer->isTestClassMethod($node)) {
            return null;
        }

        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (! $phpDocInfo instanceof PhpDocInfo) {
            return null;
        }

        if (! $phpDocInfo->hasByName('test')) {
            return null;
        }

        $hasChanged = false;

        if (! $this->phpAttributeAnalyzer->hasPhpAttribute($node, PHPUnitAttribute::TEST)) {
            array_unshift($node->attrGroups, new AttributeGroup([new Attribute(new Name('Test'))]));
            $this->addTestUseImport();
            $hasChanged = true;
        }

        if ($this->phpDocTagRemover->removeByName($phpDocInfo, 'test')) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);
            $hasChanged = true;
        }

        return $hasChanged ? $node : null;
    }

    private function addTestUseImport(): void
    {
        $fileNode = $this->getFile()->getFileNode();

        if (! $fileNode instanceof FileNode) {
            return;
        }

        $testAttributeType = new FullyQualifiedObjectType(PHPUnitAttribute::TEST);

        if ($fileNode->hasImport($testAttributeType)) {
            return;
        }

        $fileNode->getPendingImports()->addUseImport($testAttributeType);
    }
}
