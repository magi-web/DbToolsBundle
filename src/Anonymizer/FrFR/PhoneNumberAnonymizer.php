<?php

declare(strict_types=1);

namespace MakinaCorpus\DbToolsBundle\Anonymizer\FrFR;

use Doctrine\DBAL\Query\QueryBuilder;
use MakinaCorpus\DbToolsBundle\Anonymizer\AbstractAnonymizer;
use MakinaCorpus\DbToolsBundle\Anonymizer\Options;
use MakinaCorpus\DbToolsBundle\Anonymizer\Target as Target;
use MakinaCorpus\DbToolsBundle\Attribute\AsAnonymizer;

/**
 * Anonymize french telephone numbers.
 *
 * This will create phone number with reserved prefixes for fiction and tests:
 *   - 01 99 00 XX XX
 *   - 02 61 91 XX XX
 *   - 03 53 01 XX XX
 *   - 04 65 71 XX XX
 *   - 05 36 49 XX XX
 *   - 06 39 98 XX XX
 *
 * Under the hood, it will simple send basic strings such as: 0639980000 with
 * trailing 0's randomly replaced with something else. Formating may be
 * implemented later.
 *
 * Options are:
 *   - "mode": can be "mobile" or "landline"
 */
#[AsAnonymizer('fr_fr.phone')]
class PhoneNumberAnonymizer extends AbstractAnonymizer
{
    /**
     * {@inheritdoc}
     */
    public function anonymize(QueryBuilder $updateQuery, Target\Target $target, Options $options): void
    {
        if (!$target instanceof Target\Column) {
            throw new \InvalidArgumentException("This anonymizer only accepts Target\Column target.");
        }

        $plateform = $this->connection->getDatabasePlatform();

        $prefixExpression =  $plateform->quoteStringLiteral(
            match ($options->get('mode', 'mobile')) {
                'mobile' => '063998',
                'landline' => '026191',
                default => throw new \InvalidArgumentException('"mode" option can be "mobile", "landline"'),
            }
        );

        $escapedColumnName = $plateform->quoteIdentifier($target->column);

        $updateQuery->set(
            $escapedColumnName,
            $this->getSetIfNotNullExpression(
                $escapedColumnName,
                $plateform->getConcatExpression(
                    $prefixExpression,
                    $this->getSqlTextPadLeftExpression(
                        $this->getSqlRandomIntExpression(9999),
                        4,
                        '0'
                    ),
                ),
            ),
        );
    }
}