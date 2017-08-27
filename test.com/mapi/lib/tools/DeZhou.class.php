<?php
require_once dirname(__FILE__) . '/Poker.class.php';

/**
 * Created by PhpStorm.
 * User: L
 * Date: 2016/12/14
 * Time: 09:49
 */
class DeZhou extends Poker
{
    /**
     * @var array
     */
    protected $type = array(
        'thds' => 0,
        'ths'  => 1,
        't4'   => 2,
        'hl'   => 3,
        'th'   => 4,
        'sz'   => 5,
        't3'   => 6,
        'd2'   => 7,
        'd1'   => 8,
        'gp'   => 9,
    );
    public    $gp   = array();

    public function checkCards($cards)
    {
        if (sizeof($cards) == 5) {
            $th          = true;
            $sz          = true;
            $t4          = false;
            $t3          = false;
            $color_flag  = false;
            $figure_flag = false;
            $figures     = array();
            $colors      = array();
            foreach ($cards as $key => $value) {
                $colors[$key]  = $color = $this->colors[$value[0]];
                $figures[$key] = $figure = $this->figures[$value[1]];
                if (!$key) {
                    $color_flag = $color;
                } else {
                    if ($color_flag != $color) {
                        $th = false;
                    }
                    if ($figures[$key - 1] - 1 != $figure) {
                        $sz = false;
                    }
                }
            }
            $d = 0;
            foreach (array_count_values($figures) as $key => $value) {
                switch ($value) {
                    case 4:
                        $t4 = true;
                        break;
                    case 3:
                        $t3 = true;
                        break;
                    case 2:
                        $d++;
                        break;
                    default:
                        break;
                }
            }
            if ($figures == array(12, 3, 2, 1, 0)) {
                $sz = true;
            }
            $d2   = $d == 2;
            $d1   = $d == 1;
            $ths  = $th && $sz;
            $hl   = $t3 && $d1;
            $thds = $ths && $figures == array(12, 11, 10, 9, 8);
            $type = 'gp';
            foreach ($this->type as $key => $value) {
                if (isset($$key) && $$key) {
                    $type = $key;
                    break;
                }
            }
            $type = $this->type[$type];
            return compact('type', 'figures', 'colors');
        } else {
            $cards = array_merge($cards, $this->gp);
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
            $cards = self::combination($cards, 5);
            foreach ($cards as $key => $value) {
                $cards[$key] = array(
                    'cards' => $value,
                    'check' => $this->checkCards($value),
                );
            }
            $max = sizeof($cards) - 1;
            for ($j = 0; $j < $max; $j++) {
                for ($i = 0; $i < $max - $j; $i++) {
                    if ($this->compare($cards[$i + 1], $cards[$i])) {
                        $card          = $cards[$i];
                        $cards[$i]     = $cards[$i + 1];
                        $cards[$i + 1] = $card;
                    }
                }
            }
            return $cards[0]['check'];
        }
    }

    public function compare($player1, $player2)
    {
        $type1 = $player1['check']['type'];
        $type2 = $player2['check']['type'];
        if ($type1 != $type2) {
            return $type1 < $type2;
        }
        $figures1 = $player1['check']['figures'];
        $figures2 = $player2['check']['figures'];
        if ($figures1 == $figures2) {
            return false;
        }
        $color1 = $player1['check']['colors'];
        $color2 = $player2['check']['colors'];

        switch ($type1) {
            case 7:
                // 两对
                $figures1 = array_count_values($figures1);
                $figures2 = array_count_values($figures2);
                arsort($figures1);
                arsort($figures2);
                $figures1 = array_keys($figures1);
                $figures2 = array_keys($figures2);
                if ($figures1[1] > $figures1[2]) {
                    $value       = $figures1[1];
                    $figures1[1] = $figures1[2];
                    $figures1[2] = $value;
                }
                if ($figures2[1] > $figures2[2]) {
                    $value       = $figures2[1];
                    $figures2[1] = $figures2[2];
                    $figures2[2] = $value;
                }
                break;
            case 2:
                // 四条
            case 3:
                // 葫芦
            case 6:
                // 三条
            case 8:
                // 一对
                $figures1 = array_count_values($figures1);
                $figures2 = array_count_values($figures2);
                arsort($figures1);
                arsort($figures2);
                $figures1 = array_keys($figures1);
                $figures2 = array_keys($figures2);
                break;
            case 5:
                // 同花顺
            case 1:
                //顺子
                if ($figures1 == array(12, 3, 2, 1, 0)) {
                    return false;
                }
                if ($figures2 == array(12, 3, 2, 1, 0)) {
                    return true;
                }
                return $figures1[0] > $figures2[0];
                break;
            case 0:
                // 皇家同花顺
            case 4:
                // 同花
            default:
                // 高牌
                break;
        }
        foreach ($figures1 as $key => $value) {
            if ($value != $figures2[$key]) {
                return $value > $figures2[$key];
            }
        }
        return false;
    }

    public function play($number = 2)
    {
        if ($number < 2 || $number > 23) {
            $number = 2;
        }
        if (empty($this->cards)) {
            $this->shuffleCards();
        }
        $this->flop();
        $players = array();
        for ($i = 0; $i < $number; $i++) {
            $cards     = $this->pickCards(2);
            $players[] = array(
                'cards' => $cards,
                'check' => $this->checkCards($cards),
            );
        }
        return $this->order($players);
    }

    public function flop($cards = array())
    {
        if (empty($cards)) {
            $this->gp = array_merge($this->gp, $this->pickCards(5 - sizeof($this->gp)));
        } else {
            if (empty($this->cards)) {
                $this->shuffleCards();
            }
            foreach ($this->cards as $key => $value) {
                if (in_array($value, $cards)) {
                    unset($this->cards[$key]);
                }
            }
            $this->gp = $cards;
        }
    }
}