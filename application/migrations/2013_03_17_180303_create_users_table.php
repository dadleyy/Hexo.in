<?php

class Create_Users_Table {

	/**
	 * Make changes to the database.
	 *
	 * @return void
	 */
    public function up( ) {
        $date = new DateTime( );
        Schema::create('users', function($table) {
            $table->increments('id');
            
            $table->string('email');
            $table->string('password');
            $table->string('username');
            $table->integer('privileges')->default(0);
            
            $table->float('latitude')->default(0);
            $table->float('longitude')->default(0);
            
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('games')->default(0);
            $table->boolean('dummy_user')->default(false);
            
            $table->timestamp('last_update')->default( $date );
            
            $table->timestamps();
        });
    }

	/**
	 * Revert the changes to the database.
	 *
	 * @return void
	 */
	public function down( ) {  
    	Schema::drop('users');
	}

}