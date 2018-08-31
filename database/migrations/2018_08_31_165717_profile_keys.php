<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProfileKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('profiles', function (Blueprint $table) {
        $table->string('keyid', 255)->default('');
        $table->longtext('public_key')->nullable();
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('profiles', function (Blueprint $table) {
        $table->dropColumn('keyid');
        $table->dropColumn('public_key');
      });
    }
}
