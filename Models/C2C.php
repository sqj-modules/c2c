<?php
/**
 * Created by PhpStorm.
 * User: sunqingjiang
 * Date: 2019/10/24
 * Time: 10:09 上午
 */
namespace SQJ\Modules\C2C\Models;

use App\Models\Base;
use Illuminate\Support\Str;

class C2C extends Base
{
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? 'c2c_' . Str::snake(Str::pluralStudly(class_basename($this)));
    }
}
