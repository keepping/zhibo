<?php
require_once dirname(__FILE__) . '/Poker.class.php';

/**
 * Created by PhpStorm.
 * User: L
 * Date: 2016/12/14
 * Time: 09:49
 */
class NiuNiu extends Poker
{
    /**
     * [$figures description]
     * @var array
     */
    protected $figures = array(
        'A'  => 1,
        '2'  => 2,
        '3'  => 3,
        '4'  => 4,
        '5'  => 5,
        '6'  => 6,
        '7'  => 7,
        '8'  => 8,
        '9'  => 9,
        '10' => 10,
        'J'  => 11,
        'Q'  => 12,
        'K'  => 13,
    );
    /**
     * @var array
     */
    protected $type = array(
        'wx' => 0,
        'zd' => 1,
        'wh' => 2,
        'sh' => 3,
        'nn' => 4,
        'n9' => 5,
        'n8' => 6,
        'n7' => 7,
        'n6' => 8,
        'n5' => 9,
        'n4' => 10,
        'n3' => 11,
        'n2' => 12,
        'n1' => 13,
        'mn' => 14,
    );

    public function checkCards($cards)
    {
        // 冒泡排序
        $max = sizeof($cards) - 1;
        for ($j = 0; $j < $max; $j++) {
            for ($i = 0; $i < $max - $j; $i++) {
                if ($this->figures[$cards[$i][1]] < $this->figures[$cards[$i + 1][1]]) {
                    $card          = $cards[$i];
                    $cards[$i]     = $cards[$i + 1];
                    $cards[$i + 1] = $card;
                }
            }
        }
        $h  = 0;
        $yn = false;

        $figures = array();
        $colors  = array();

        $figures_flag  = array();
        $figures_count = array();
        foreach ($cards as $key => $card) {
            $figure = $this->figures[$card[1]];
            if (isset($figures_count[$figure])) {
                $figures_count[$figure]++;
            } else {
                $figures_count[$figure] = 1;
            }
            if ($figure > 10) {
                $h++;
                $figures_flag[] = 10;
            } else {
                $figures_flag[] = $figure;
            }
            $figures[$key] = $figure;
            $colors[$key]  = $this->colors[$card[0]];
        }
        $res  = array_sum($figures_flag) % 10;
        $yn   = false;
        $data = self::combination($figures_flag, 3);
        foreach ($data as $value) {
            if (array_sum($value) % 10 == 0) {
                $yn = true;
                break;
            }
        }
        $wh = $h == 5;
        $sh = !$res && $h == 4;
        $wx = array_sum($figures) < 10;
        $zd = false;
        if (sizeof($figures_count) < 3) {
            foreach ($figures_count as $value) {
                if ($value == 4) {
                    $zd = true;
                    break;
                }
            }
        }
        // 牌型
        $type = 'mn';
        foreach ($this->type as $key => $value) {
            if (isset($$key) && $$key) {
                $type = $key;
            }
        }
        if ($yn && $type == 'mn') {
            if (!$res) {
                $type = 'nn';
            } else {
                $type = 'n' . $res;
            }
        }
        $type = $this->type[$type];
        return compact('type', 'figures', 'colors');
    }

    /**
     * 牌组大小
     * @param $player1
     * @param $player2
     * @return bool
     */
    public function compare($player1, $player2)
    {
        $type1 = $player1['check']['type'];
        $type2 = $player2['check']['type'];
        if ($type1 != $type2) {
            return $type1 < $type2;
        }
        $figure1 = $player1['check']['figures'];
        $figure2 = $player2['check']['figures'];
        $color1  = $player1['check']['colors'];
        $color2  = $player2['check']['colors'];

        if ($figure1[0] == $figure2[0]) {
            return $color1[0] < $color2[0];
        } else {
            return $figure1[0] > $figure2[0];
        }
    }

    public function play($number = 3)
    {
        if ($number < 2 || $number > 10) {
            $number = 2;
        }
        $this->shuffleCards();
        $players = array();
        for ($i = 0; $i < $number; $i++) {
            $cards     = $this->pickCards(5);
            $players[] = array(
                'cards' => $cards,
                'check' => $this->checkCards($cards),
            );
        }
        return $this->order($players);
    }
}
