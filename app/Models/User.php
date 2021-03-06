<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail {

  use Notifiable;

  //The attributes that are mass assignable.
  protected $fillable = [
    'first_name', 'last_name', 'email', 'password',
  ];

  //The attributes that should be hidden for arrays.
  protected $hidden = [
    'password', 'remember_token',
  ];

  //The attributes that should be cast to native types.
  protected $casts = [
    'email_verified_at' => 'datetime',
    'is_admin' => 'boolean'
  ];

  public static function getSortedUsersByDaysResponses($date) {
    $users = self::with(['response' => function($query) use ($date) {
      $query->whereDate('created_at', $date);
    }])->get();

    $results = [
      'positive' => [],
      'negative' => [],
      'no_response' => []
    ];

    foreach($users as $user) {
      $key = $user->response == null ? 'no_response' : ($user->response->response_type_id == ResponseType::POSITIVE ? 'positive' : 'negative');

      $results[$key][] = $user;
    }

    return $results;
  }

  public static function getUsersWithDaysResponses($date) {
    $users = self::with(['response' => function($query) use ($date) {
      $query->whereDate('created_at', $date);
    }])->get();

    $results = [
      'positive' => [],
      'negative' => [],
      'no_response' => []
    ];

    foreach($users as $user) {
      $key = $user->response == null ? 'no_response' : ($user->response->response_type_id == ResponseType::POSITIVE ? 'positive' : 'negative');

      $results[$key][] = [
        'name' => $user->full_name,
        'email' => $user->email,
        'created_at' => $user->response == null ? null : $user->response->getFriendlyCreatedAtTime()
      ];
    }

    return $results;
  }

  public function getFullNameAttribute() {
    return $this->first_name . ' ' . $this->last_name;
  }

  public function responses() {
    return $this->hasMany(Response::class);
  }

  /**
   * Same as responses() but only returns one result
   * This is useful for mass queries with filters (ex: select all users' responses for a specific day)
   */
  public function response() {
    return $this->hasOne(Response::class);
  }

  public function hasRespondedToday() {
    $today = \Carbon\Carbon::now()->format('Y-m-d');
    $exists = $this->responses()->whereDate('created_at', $today)->exists();

    return $exists;
  }

  public function logTodaysResponse($responseTypeId) {
    $response = new Response;
    $response->user_id = $this->id;
    $response->response_type_id = $responseTypeId;
    $response->save();
  }

  public function isAdmin() {
    return $this->is_admin;
  }

  public function setIsAdmin($isAdmin) {
    $this->is_admin = $isAdmin;
    $this->save();
  }

}
