<?php

declare(strict_types=1);

namespace Janklan\SimpleDateTime\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/**
 * Detects comparison operators used on DateInterface, TimeInterface, or DateTimeInterface.
 *
 * These types should use explicit comparison methods (isBefore, isAfter, isSameDateAs, etc.)
 * rather than PHP's comparison operators which can produce unexpected results.
 *
 * @implements Rule<BinaryOp>
 */
final class ForbidDateTimeComparisonRule implements Rule
{
    private const DATE_TIME_TYPES = [
        \DateTimeInterface::class,
        \Janklan\SimpleDateTime\DateInterface::class,
        \Janklan\SimpleDateTime\TimeInterface::class,
    ];

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isComparisonOperator($node)) {
            return [];
        }

        $leftType = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        $leftIsDateTime = $this->isDateTimeType($leftType);
        $rightIsDateTime = $this->isDateTimeType($rightType);

        if (!$leftIsDateTime && !$rightIsDateTime) {
            return [];
        }

        $operator = $this->getOperatorSymbol($node);

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Comparison operator "%s" should not be used with DateInterface/TimeInterface. Use isBefore(), isAfter(), or isSameDateAs()/isSameTimeAs() instead.',
                    $operator
                )
            )->identifier('simpleDateTime.forbiddenComparison')->build(),
        ];
    }

    private function isComparisonOperator(BinaryOp $node): bool
    {
        return $node instanceof BinaryOp\Smaller
            || $node instanceof BinaryOp\SmallerOrEqual
            || $node instanceof BinaryOp\Greater
            || $node instanceof BinaryOp\GreaterOrEqual
            || $node instanceof BinaryOp\Equal
            || $node instanceof BinaryOp\Identical
            || $node instanceof BinaryOp\NotEqual
            || $node instanceof BinaryOp\NotIdentical
            || $node instanceof BinaryOp\Spaceship;
    }

    private function isDateTimeType(\PHPStan\Type\Type $type): bool
    {
        $type = TypeCombinator::removeNull($type);

        foreach (self::DATE_TIME_TYPES as $dateTimeClass) {
            if ((new ObjectType($dateTimeClass))->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }

    private function getOperatorSymbol(BinaryOp $node): string
    {
        return match (true) {
            $node instanceof BinaryOp\Smaller => '<',
            $node instanceof BinaryOp\SmallerOrEqual => '<=',
            $node instanceof BinaryOp\Greater => '>',
            $node instanceof BinaryOp\GreaterOrEqual => '>=',
            $node instanceof BinaryOp\Equal => '==',
            $node instanceof BinaryOp\Identical => '===',
            $node instanceof BinaryOp\NotEqual => '!=',
            $node instanceof BinaryOp\NotIdentical => '!==',
            $node instanceof BinaryOp\Spaceship => '<=>',
            default => '?',
        };
    }
}
