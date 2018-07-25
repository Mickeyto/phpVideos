<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/10
 * Time: 18:58
 */

namespace core\Command;


class CliProgressBar
{
    const COLOR_FORMAT = "\033[%sm";
    public $colors = [];
    public $backgroundColors = [];
    public $highIntenstyColors = [];

    public $currentChar = '>';
    public $defaultChar = '=';
    public $barLength = 40;
    public $step = 40;
    public $currentStep = 0;
    public $network = 0;

    /**
     * CliProgressBar constructor.
     * @param int $barLength
     */
    public function __construct(int $barLength=0)
    {
        if($barLength > 0){
            $this->barLength = $barLength;
        }
        $this->setColor();
        $this->output();
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * @param $step
     * @return $this
     */
    public function setStep(int $step):self
    {
        $this->step = $step;
        return $this;
    }

    public function getStep():int
    {
        return $this->step;
    }

    public function setCurrentStep(int $currentStep):self
    {
        $this->currentStep = $currentStep;

        if($this->currentStep > $this->step){
            $this->currentStep = $this->step;
        }

        return $this;
    }

    public function getCurrentStep():int
    {
        return $this->currentStep;
    }

    /**
     * @param int $value
     * @return CliProgressBar
     */
    public function setNetwork(int $value):self
    {
        $this->network = $value;
        return $this;
    }

    /**
     * @return int
     */
    public function getNetwork():int
    {
        $network = $this->network < 1 ? 1024*8 : $this->network;
        return $network;
    }

    /**
     * @param int $currentStep
     * @param bool $initStep
     * @return $this
     */
    public function progress(int $currentStep=1,bool $initStep=true)
    {
        if(!$initStep){
            $step = $this->getCurrentStep() + $currentStep;
            $this->setCurrentStep($step);
        } else {
            $this->setCurrentStep($currentStep);
        }

        $this->output();

        return $this;
    }

    /**
     * @param int $startColor
     * @param int $endColor 0 重置颜色
     * @return $this
     */
    public function setColor(int $startColor=32, int $endColor=0):self
    {
        $this->colors = [
            sprintf(self::COLOR_FORMAT, $startColor),
            sprintf(self::COLOR_FORMAT, $endColor),
        ];

        return $this;
    }

    /**
     * @return $this
     */
    public function colorToGreen():self
    {
        $this->setColor(32);
        return $this;
    }

    /**
     * @return $this
     */
    public function colorToRed():self
    {
        $this->setColor(31);
        return $this;
    }

    /**
     * @param string $char
     * @return CliProgressBar
     */
    public function setDefaultChar(string $char):self
    {
        $this->defaultChar = $char;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultChar():?string
    {
        return $this->defaultChar;
    }

    /**
     * @return string
     */
    public function draw():string
    {
        $step = $this->getStep();
        $currentStep = $this->getCurrentStep();

        $currentStepLength = ($currentStep / $step) * $this->barLength;
        $defaultFillCharLength = $this->barLength - $currentStepLength;

        $proStepNumber = ($currentStep / $this->step) * 100;
        $proStepNumber = number_format($proStepNumber, 1, '.', ' ');

        $startColor = $this->colors[0];
        $endColor = $this->colors[1];

        $defaultFillChar = str_repeat($this->defaultChar, $defaultFillCharLength);

        $defaultFillChar = "\033[0;91m{$defaultFillChar}\033[0m";
        $currentStepFormat = $this->fileSizeToMb($currentStep);
        $stepFormat = $this->fileSizeToMb($step);
        $networkFormat = $this->fileSizeToMb($this->getNetwork());

        $stepFillChar = str_repeat($this->currentChar, $currentStepLength);
        $bar = sprintf('%s%s  %.1f%%（%s/%s）%s/s ', $stepFillChar, $defaultFillChar, $proStepNumber, $currentStepFormat, $stepFormat, $networkFormat);

        return sprintf("\r%s%s%s", $startColor, $bar, $endColor);
    }

    public function output()
    {
        echo $this->draw();
    }

    /**
     * @param string $title
     */
    public function setHeaderTitle(string $title='test')
    {
        $title = sprintf("\nTitle:          \033[0;92m%s\033[0m\n\n", $title);
        echo $title;
    }

    /**
     * @param string $type
     */
    public function setHeaderType(string $type='MP4')
    {
        $type = sprintf("\nType:          \033[0;92m%s\033[0m\n\n", $type);
        echo $type;
    }

    /**
     * @param int $size
     * @param int $powValue
     * @return string
     */
    public function fileSizeToMb(int $size, int $powValue=2):string
    {
        $sizeToMb = $size / pow(1024, $powValue);

        return round($sizeToMb, 2) . 'Mb';
    }

    public function end()
    {
        echo PHP_EOL;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->draw();
    }

}