<?php

declare(strict_types=1);

namespace Tests\unit;

use PHPUnit\Framework\TestCase;

use DateTime;
use Statistics\Dto\ParamsTo;
use Statistics\Dto\StatisticsTo;
use Statistics\Enum\StatsEnum;
use SocialPost\Hydrator\FictionalPostHydrator;
use Statistics\Calculator\Factory\StatisticsCalculatorFactory;


/**
 * Class AveragePostsPerUserPerMonthCalculatorTest
 *
 * @package Tests\unit
 */
class AveragePostsPerUserPerMonthCalculatorTest extends TestCase
{
    private $posts;

    private function statisticsToArray(StatisticsTo $statistics)
    {
        return array('name' => $statistics->getName(), 'value' => $statistics->getValue(), 'splitPeriod' => $statistics->getSplitPeriod(), 'units' => $statistics->getUnits());
    }

    private function createPostArray(string $id, string $fromName, string $fromId, string $message, string $type, string $createdTime)
    {
        return array(
            'id' => $id,
            'from_name' => $fromName,
            'from_id' => $fromId,
            'message' => $message,
            'type' => $type,
            'created_time' => (new DateTime($createdTime))->format(DateTime::ATOM)
        );
    }


    protected function setUp(): void
    {
        $this->posts = array(
            $this->createPostArray('0', 'name1', 'id_1', 'msg', 'type', '2022-02-01'),
            $this->createPostArray('0', 'name1', 'id_1', 'msg', 'type', '2022-02-01'),
            $this->createPostArray('0', 'name2', 'id_2', 'msg', 'type', '2022-02-01'),
            $this->createPostArray('0', 'name3', 'id_3', 'msg', 'type', '2022-04-01'),
            $this->createPostArray('0', 'name4', 'id_4', 'msg', 'type', '2022-05-01'),
            $this->createPostArray('0', 'name4', 'id_4', 'msg', 'type', '2022-05-01'),
            $this->createPostArray('0', 'name5', 'id_5', 'msg', 'type', '2022-05-01'),
            $this->createPostArray('0', 'name6', 'id_6', 'msg', 'type', '2022-05-01'),
        );
    }

    private function assertStatistics(array $statistics, array $expected)
    {
        $this->assertEquals(count($expected), count($statistics));
        foreach ($statistics as $child) {
            $stats = $this->statisticsToArray($child);
            $expectedStats = $expected[$stats['splitPeriod']];
            $this->assertEquals($stats, $expectedStats);
        }
    }

    private function setupData()
    {
        $postHydrator = new FictionalPostHydrator();
        $calculatorFactory = new StatisticsCalculatorFactory();
        $startDate = new DateTime("2022-01-01");
        $endDate = new DateTime("2022-05-01");
        $params = (new ParamsTo())
            ->setStatName(StatsEnum::AVERAGE_POSTS_NUMBER_PER_USER_PER_MONTH)
            ->setStartDate($startDate)
            ->setEndDate($endDate);
        $averagePerUserPerMonthCalculator = $calculatorFactory->create([$params]);

        return array($averagePerUserPerMonthCalculator, $postHydrator);
    }

    /**
     * @test
     */
    public function testAverageCountedCorrectly(): void
    {
        [$averagePerUserPerMonthCalculator, $postHydrator] = $this->setupData();
        $posts = array_map(array($postHydrator, 'hydrate'), $this->posts);

        foreach ($posts as $post) {
            $averagePerUserPerMonthCalculator->accumulateData($post);
        }
        $result = $averagePerUserPerMonthCalculator->calculate();

        $expected = array(
            'Month 2' => array('name' => 'average-posts-per-user', 'value' => 1.5, 'splitPeriod' => 'Month 2', 'units' => 'posts'),
            'Month 4' => array('name' => 'average-posts-per-user', 'value' => 1, 'splitPeriod' => 'Month 4', 'units' => 'posts'),
            'Month 5' => array('name' => 'average-posts-per-user', 'value' => 1.33, 'splitPeriod' => 'Month 5', 'units' => 'posts')
        );

        $children = $result->getChildren()[0]->getChildren();
        $this->assertStatistics($children, $expected);
    }
}
