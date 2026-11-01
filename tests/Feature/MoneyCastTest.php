<?php

namespace Tests\Feature;

use App\Casts\MoneyCast;
use App\Support\Money\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyCastTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('money_cast_probes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('total_minor')->nullable();
            $table->char('currency', 3)->nullable();
            $table->bigInteger('fee_amount')->nullable();
        });
    }

    public function test_round_trips_through_minor_and_currency_columns(): void
    {
        $probe = MoneyCastProbe::create(['total' => Money::of('1250.50', 'BDT')]);

        $row = DB::table('money_cast_probes')->find($probe->id);
        $this->assertSame(125050, (int) $row->total_minor);
        $this->assertSame('BDT', $row->currency);

        $this->assertTrue($probe->fresh()->total->equals(Money::of('1250.50', 'BDT')));
    }

    public function test_custom_amount_column_shares_the_currency_column(): void
    {
        $probe = MoneyCastProbe::create(['fee' => Money::of('3.75', 'USD')]);

        $this->assertSame(375, (int) DB::table('money_cast_probes')->find($probe->id)->fee_amount);
        $this->assertSame('USD', $probe->fresh()->fee->currency->value);
    }

    public function test_null_stays_null(): void
    {
        $probe = MoneyCastProbe::create(['total' => null]);

        $this->assertNull($probe->fresh()->total);
    }

    public function test_floats_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MoneyCastProbe(['total' => 12.5]);
    }
}

class MoneyCastProbe extends Model
{
    public $timestamps = false;

    protected $fillable = ['total', 'fee'];

    protected function casts(): array
    {
        return [
            'total' => MoneyCast::class,
            'fee' => MoneyCast::class.':fee_amount',
        ];
    }
}
