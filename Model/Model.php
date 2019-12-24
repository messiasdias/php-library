<?php

/**
 *  Model Class
 */
namespace App\Model;
use App\App;
use App\Database\Validator;
use App\Database\DB;
use App\Model\ModelInterface;

class Model implements ModelInterface
{	
	
	public  $id, $created, $updated ;


	/* Seting Table, if this not exists, create on construct Method*/
	public function __construct(array $data=[]){

		foreach ( $data as $key => $value) {
			if ( array_key_exists( $key, $data) && property_exists(get_called_class() , $key) ){
				$this->$key = $value;
			}
		}

	} 

	private function db(){
		return  new DB( get_called_class() );
	}

	
	//Object exists
	public function exists(){

		if (  self::db()->exists(strtolower('id'), $this->id )  ) {
			return true;
		}

		return false;
	}

	//Find a User Object
	public static function find($col, $value){
		$user =  self::db()->select([strtolower($col), $value] );
		return isset($user->scalar) ? false : $user;
	}

	//Finde and Get all Users on Object with optional pagination
	public static function all(array $paginate=null ){

		if ( !is_null($paginate) ){
		  return  self::db()->paginate($paginate[0], $paginate[1],$paginate[2]);
		}

		return  self::db()->select( '*');
	}





	/*
	
	Clears the array of validations as per data arrey,
	only validates what arrived in the request as form data
	
	*/
	private function clear($validations, $data=null){

		if( is_null($data) ){

			foreach ( (array) $this as $key => $value) {
				if ( isset($this->$key) ){
					$data[$key] = $this->$key ;
				}
			}
			
			if ( (isset($this->pass) && ( count_chars($this->pass) != 60 ))) {
				$data['pass']  = password_hash($this->pass, PASSWORD_BCRYPT);
			}			
		}
		

		if ( ( !is_null($data) && isset($data['pass']) ) &&  ( count_chars($data['pass']) != 60 ) ) {
			$data['pass'] = password_hash($data['pass'], PASSWORD_BCRYPT);
		}

		foreach ( $validations as $key => $value) {
			if ( !array_key_exists($key, $data) | !isset( $data[$key] ) ){
				unset($validations[$key])  ;
			}
		}

		foreach ( $data as $key => $value) {
			if ( !array_key_exists( $key, $validations) ){
				unset($data[$key])  ;
			}
		}

		return (object) [ 'data' => (array) $data, 'validations' => (array) $validations ];

	}


	//Get Table Name
	public function table(){
		if( !isset( self::$table ) ){
			$table =  substr(get_called_class() ,strripos(get_called_class(), '\\')+1 , strlen(get_called_class())-1 );
			return strtolower($table).'s';
		}else{
			return self::$table;
		}
	}


	//Save Data - Save data at the end of create and update methods
	public function save(array $data, array $validations,$noexists=null){
		
		$clear = self::clear($validations, $data);
		$validate = App::validate($clear->data, $clear->validations, get_called_class() );

		if(!$validate->errors){	
			
			foreach ($validate->data as $key => $value) {
				if ( preg_match('/^confirm_[a-zA-Z0-9]{1,}$/', $key) ){
					unset($clear->data[$key]);
				}
			} 	
			
			return  self::db()->save($clear->data);
				
		}else{
			return (object) [ 'status'=> false , 'msg' => "Error while Validating data!" , 'data'=> $validate->data, 'errors' => $validate->errors];
		} 


	}


	//Create and Update methods - Validations
	public function create (){}
	public function update (){}
	

	//Delete	
	public function delete(){
		$validations = [
				'id' => 'int|mincount:1|exists:'.explode("\\", get_called_class() )[ count( explode("\\", get_called_class() ) ) -1],
			];
		$clear = self::clear($validations, (array) $this );	
		$validate = App::validate($clear->data, $clear->validations, get_called_class() );

		if(!$validate->errors){	
			//return (object) [ 'status'=> true , 'msg' => 'Teste delete OK!' , 'data' => $validate->data ];
			return  self::db()->delete($validate->data);
		}else {
			return (object) [ 'status'=> false , 'msg' => 'Error while Deleting object!' , 'data'=> $validate->data ];
		}

	}




}



?>