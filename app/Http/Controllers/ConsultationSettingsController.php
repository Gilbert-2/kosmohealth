<?php

namespace App\Http\Controllers;

use App\Repositories\ConsultationSettingsRepository;

class ConsultationSettingsController extends Controller
{
    private ConsultationSettingsRepository $repo;

    public function __construct(ConsultationSettingsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function show()
    {
        $data = $this->repo->get();
        // Do not expose default_preferred_datetime (removed), already not returned
        return $this->ok($data);
    }
}


