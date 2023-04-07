<?php

namespace Statistics\Calculator;

use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

/**
 * Class Calculator
 *
 * @package Statistics\Calculator
 */
class AveragePostsPerUserPerMonthCalculator extends AbstractCalculator
{

    protected const UNITS = 'posts';
    private const POSTS_KEY = 'posts';
    private const AUTHORS_KEY = 'authors';

    private const POSTS_ROUNDING_PRECISION = 2;

    /**
     * @var array
     */
    private $postsAndUsersPerMonth = [];



    /**
     * @param SocialPostTo $postTo
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $postMonth = intval($postTo->getdate()->format('m'));
        $authorId = $postTo->getAuthorId();
        if (!array_key_exists($postMonth, $this->postsAndUsersPerMonth)) {
            $this->postsAndUsersPerMonth[$postMonth] = array(self::AUTHORS_KEY => [], self::POSTS_KEY => 0);
        }
        $this->postsAndUsersPerMonth[$postMonth][self::AUTHORS_KEY][$authorId] = $authorId;
        $this->postsAndUsersPerMonth[$postMonth][self::POSTS_KEY] += 1;
    }

    /**
     * @return StatisticsTo
     */
    protected function doCalculate(): StatisticsTo
    {
        $stats = new StatisticsTo();
        foreach ($this->postsAndUsersPerMonth as $month => [self::POSTS_KEY => $postsThisMonth, self::AUTHORS_KEY => $authors]) {
            $authorsCount = count($authors);
            $average = $postsThisMonth > 0 ? $postsThisMonth / $authorsCount : 0;
            
            $child = (new StatisticsTo())
                ->setName($this->parameters->getStatName())
                ->setSplitPeriod(sprintf("Month %d", $month))
                ->setValue(round($average, self::POSTS_ROUNDING_PRECISION))
                ->setUnits(self::UNITS);

            $stats->addChild($child);
        }

        return $stats;
    }
}
