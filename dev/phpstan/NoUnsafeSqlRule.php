<?php

namespace WpFluent\PHPStanRules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Scalar\Encapsed;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

final class NoUnsafeSqlRule implements Rule
{
    private const DANGEROUS_METHODS = [
        'query', 'unprepared', 'select', 'insert', 'update', 'delete',
        'statement', 'affectingStatement', 'rawCursor', 'raw',
        'whereRaw', 'havingRaw', 'orderByRaw', 'groupByRaw',
    ];

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        // 1. Raw function calls
        if ($node instanceof FuncCall) {
            $name = $node->name instanceof Node\Name ? $node->name->toString() : null;
            if ($name !== null && $this->isDangerousFunc($name)) {
                $errors = array_merge($errors, $this->checkSqlArg($node));
            }
        }

        // 2. Method calls ($app->db->query(), Builder methods)
        if ($node instanceof MethodCall) {
            $method = $node->name instanceof Identifier ? $node->name->toString() : null;
            if ($method === null) return $errors;

            // Raw SQL method
            if (in_array($method, self::DANGEROUS_METHODS, true)) {
                $errors = array_merge($errors, $this->checkSqlArg($node));
            }

            // Query builder dynamic columns
            if (in_array($method, ['orderBy', 'groupBy', 'having', 'where'], true)) {
                $firstArg = $node->args[0]->value ?? null;
                if ($this->isPotentiallyUnsafeExpr($firstArg)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf('Possible SQL injection: dynamic column used in %s() on query builder.', $method)
                    )->build();
                }
            }
        }

        // 3. Static calls (ORM)
        if ($node instanceof StaticCall) {
            $method = $node->name instanceof Identifier ? $node->name->toString() : null;
            if ($method === null) return $errors;

            if (in_array($method, ['orderBy', 'groupBy', 'having', 'where'], true)) {
                $firstArg = $node->args[0]->value ?? null;
                if ($this->isPotentiallyUnsafeExpr($firstArg)) {
                    $class = $node->class instanceof Node\Name ? $node->class->toString() : '<unknown>';
                    $errors[] = RuleErrorBuilder::message(
                        sprintf('Possible SQL injection: dynamic column used in %s::%s().', $class, $method)
                    )->build();
                }
            }
        }

        return $errors;
    }

    private function isDangerousFunc(string $name): bool
    {
        return in_array($name, ['mysql_query', 'mysqli_query'], true)
            || in_array($name, self::DANGEROUS_METHODS, true);
    }

    /** @return \PHPStan\Rules\RuleError[] */
    private function checkSqlArg(Node $callNode): array
    {
        $errors = [];
        $sqlArg = $callNode->args[0]->value ?? null;
        $bindings = $callNode->args[1]->value ?? null;

        if ($sqlArg === null) return $errors;

        // Interpolated variables inside SQL string
        if ($sqlArg instanceof Encapsed) {
            $errors[] = RuleErrorBuilder::message('SQL Injection Risk: interpolated variable inside SQL string.')->build();
        }

        // Concatenated SQL string
        if ($sqlArg instanceof Concat) {
            $errors[] = RuleErrorBuilder::message('SQL Injection Risk: SQL built using string concatenation.')->build();
        }

        // Variable inside string
        if ($sqlArg instanceof String_ && preg_match('/\$\w+/', $sqlArg->value)) {
            $errors[] = RuleErrorBuilder::message('SQL Injection Risk: variable appears inside SQL string.')->build();
        }

        // ORDER BY/GROUP BY dynamic bindings in raw SQL
        if ($sqlArg instanceof String_ && preg_match('/\b(order\s+by|group\s+by)\b/i', $sqlArg->value)) {
            if ($this->isPotentiallyUnsafeExpr($bindings)) {
                $errors[] = RuleErrorBuilder::message('Possible SQL Injection in ORDER BY/GROUP BY: dynamic column used.')->build();
            }
        }

        // Placeholder vs binding mismatch
        if ($sqlArg instanceof String_ && $bindings instanceof Node\Expr\Array_) {
            $placeholders = substr_count($sqlArg->value, '?');
            $bindingCount = count($bindings->items);
            if ($placeholders !== $bindingCount) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf('Binding count mismatch: %d placeholders but %d bindings supplied.', $placeholders, $bindingCount)
                )->build();
            }
        }

        return $errors;
    }

    private function isPotentiallyUnsafeExpr($expr): bool
    {
        if ($expr === null) return false;
        if ($expr instanceof Variable) return true;
        if ($expr instanceof Encapsed) return true;
        if ($expr instanceof Concat) return true;
        if ($expr instanceof String_ && preg_match('/\$\w+/', $expr->value)) return true;

        return true;
    }
}
