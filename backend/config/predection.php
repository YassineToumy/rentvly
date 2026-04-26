<?php

return [
    'python_path' => env('PYTHON_PATH', 'python'),
    'script_path' => env('PREDICTION_SCRIPT_PATH', base_path('../model-catboost.py')),
];