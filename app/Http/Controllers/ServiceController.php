<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class ServiceController extends Controller
{
    public function soketiStatus()
    {
        $process = new Process(['../scripts/check-soketi.sh']);
        $process->run();

        if ($process->isSuccessful() && (int) $process->getOutput()) {
            return $this->success(['status' => true]);
        }

        return $this->success(['status' => false]);
    }

    public function signalStatus()
    {
        $process = new Process(['../scripts/check-signal.sh']);
        $process->run();

        if ($process->isSuccessful() && (int) $process->getOutput()) {
            return $this->success(['status' => true]);
        }

        return $this->success(['status' => false]);
    }

    public function iceStatus()
    {
        $process = new Process(['../scripts/check-ice.sh']);
        $process->run();

        if ($process->isSuccessful() && (int) $process->getOutput()) {
            return $this->success(['status' => true]);
        }

        return $this->success(['status' => false]);
    }
}
