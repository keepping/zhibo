<?php

class Poker
{
    /**
     * @var array
     */
    protected $colors = array(
        'spade'   => 0,
        'heart'   => 1,
        'club'    => 2,
        'diamond' => 3,
    );
    /**
     * @var array
     */
    protected $figures = array(
        '2'  => 0,
        '3'  => 1,
        '4'  => 2,
        '5'  => 3,
        '6'  => 4,
        '7'  => 5,
        '8'  => 6,
        '9'  => 7,
        '10' => 8,
        'J'  => 9,
        'Q'  => 10,
        'K'  => 11,
        'A'  => 12,
    );
    /**
     * @var array
     */
    protected $type = array(
        'bz'  => 0,
        'ths' => 1,
        'th'  => 2,
        'sz'  => 3,
        'dz'  => 4,
        'dp'  => 5,
    );
    /**
     * @var array
     */
    protected $cards;

    public function shuffleCards()
    {
        $cards = array();
        foreach ($this->colors as $color => $value) {
            foreach ($this->figures as $figure => $value2) {
                $cards[] = array($color, $figure);
            }
        }
        shuffle($cards);
        $this->cards = $cards;
    }

    /**
     * 判断牌的种类
     * @param $cards
     * @return array
     */
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
        $bz          = true;
        $th          = true;
        $sz          = true;
        $dz          = false;
        $color_flag  = '';
        $figure_flag = 0;

        $figures = array();
        $colors  = array();
        foreach ($cards as $key => $card) {
            $figures[$key] = $figure = $this->figures[$card[1]];
            $colors[$key]  = $color  = $this->colors[$card[0]];
            if ($key) {
                // 同花
                if ($color != $color_flag) {
                    $th = false;
                }
                // 豹子
                if ($figure != $figure_flag) {
                    $bz = false;
                }
                // 对子
                if ($figure == $figure_flag) {
                    $dz = true;
                }
                // 顺子
                if ($figures[$key - 1] - 1 != $figure) {
                    $sz = false;
                }
            }
            $color_flag  = $color;
            $figure_flag = $figure;
        }
        // 顺子
        if ($figures == array(12, 1, 0)) {
            $figures = array(1, 0, -1);
            $sz      = true;
        }
        // 对子
        $dz = $dz && !$bz;
        // 同花顺
        $ths = $th && $sz;
        // 牌型
        // $type = 'dp';
        $type = 5;
        foreach ($this->type as $key => $value) {
            if (isset($$key) && $$key) {
                $type = $this->type[$key];
                break;
            }
        }
        return compact('type', 'figures', 'colors');
    }

    /**
     * @return array|bool
     */
    public function pickCards($number = 3)
    {
        if (empty($this->cards)) {
            $this->shuffleCards();
        }
        if (sizeof($this->cards) < $number) {
            return false;
        }
        $cards = array();
        for ($i = 0; $i < $number; $i++) {
            $cards[] = array_shift($this->cards);
        }
        return $cards;
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

        switch ($type1) {
            case 0:
                return $figure1[0] > $figure2[0];
                break;
            case 1:
            case 3:
                if ($figure1 == $figure2) {
                    return $color1[0] < $color2[0];
                } else {
                    return $figure1[0] > $figure2[0];
                }
                break;
            case 4:
                $dui1 = $figure1[1];
                $dui2 = $figure2[1];
                if ($dui1 != $dui2) {
                    return $dui1 > $dui2;
                }
                $dui1 = array_sum($figure1) - $dui1;
                $dui2 = array_sum($figure2) - $dui2;
                if ($dui1 == $dui2) {
                    $i1 = $figure1[0] == $dui1 ? 2 : 0;
                    $i2 = $figure2[0] == $dui2 ? 2 : 0;
                    return $color1[$i1] < $color2[$i2];
                } else {
                    return $dui1 > $dui2;
                }
                break;
            case 2:
            default:
                foreach ($figure1 as $key => $value) {
                    if ($value != $figure2[$key]) {
                        return $value > $figure2[$key];
                    }
                }
                return $color1[0] < $color2[0];
                break;
        }
    }

    public function play($number = 3)
    {
        if ($number < 2 || $number > 17) {
            $number = 2;
        }
        $this->shuffleCards();
        $players = array();
        for ($i = 0; $i < $number; $i++) {
            $cards     = $this->pickCards();
            $players[] = array(
                'cards' => $cards,
                'check' => $this->checkCards($cards),
            );
        }
        return $this->order($players);
    }

    public function order($players)
    {
        $max = sizeof($players) - 1;
        for ($j = 0; $j < $max; $j++) {
            for ($i = 0; $i < $max - $j; $i++) {
                if ($this->compare($players[$i + 1], $players[$i])) {
                    $player          = $players[$i];
                    $players[$i]     = $players[$i + 1];
                    $players[$i + 1] = $player;
                }
            }
        }
        return $players;
    }
    protected static function combination($array, $number)
    {
        $r = array();
        $n = count($array);
        if ($number <= 0 || $number > $n) {
            return $r;
        }
        for ($i = 0; $i < $n; $i++) {
            $t = array($array[$i]);
            if ($number == 1) {
                $r[] = $t;
            } else {
                $b = array_slice($array, $i + 1);
                $c = self::combination($b, $number - 1);
                foreach ($c as $v) {
                    $r[] = array_merge($t, $v);
                }
            }
        }
        return $r;
    }
}
