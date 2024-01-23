<?php
namespace Bang;
Core::mark('Bang\UserFlag.php');

class UserFlag extends Bitwise {
	protected
		$value			= 0,
		$store;
	const
		BANNED			= 1,
		SUSPENDED		= 2,
		BOT				= 4,
		GUEST			= 8,
		MEMBER			= 16,
		CREATOR			= 32,
		ARTIST			= 64,
		ACTOR			= 128,
		WRITER			= 256,
		EDITOR			= 512,
		FAN				= 1024,
		CUSTOMER		= 2048,
		CLIENT			= 4096,
		SPECIAL			= 8192,
		VIP				= 16384,
		POI				= 32768,
		STAFF			= 65536,
		MODERATOR		= 131072,
		ADMINISTRATOR	= 262144,
		OWNER			= 524288;

	function banned(bool $b = true) {
		$this->set(self::BANNED, $b);
	}
	function isBanned() {
		return $this->has(self::BANNED);
	}
	function suspended(bool $b = true) {
		$this->set(self::SUSPENDED, $b);
	}
	function isSuspended() {
		return $this->has(self::SUSPENDED);
	}
	function bot(bool $b = true) {
		$this->set(self::BOT, $b);
	}
	function isBot() {
		return $this->has(self::BOT);
	}
	function guest(bool $b = true) {
		$this->set(self::GUEST, $b);
	}
	function isGuest() {
		return $this->has(self::GUEST);
	}
	function member(bool $b = true) {
		$this->set(self::MEMBER, $b);
	}
	function isMember() {
		return $this->has(self::MEMBER);
	}
	function creator(bool $b = true) {
		$this->set(self::CREATOR, $b);
	}
	function isCreator() {
		return $this->has(self::CREATOR);
	}
	function artist(bool $b = true) {
		$this->set(self::ARTIST, $b);
	}
	function isArtist() {
		return $this->has(self::ARTIST);
	}
	function actor(bool $b = true) {
		$this->set(self::ACTOR, $b);
	}
	function isActor() {
		return $this->has(self::ACTOR);
	}
	function writer(bool $b = true) {
		$this->set(self::WRITER, $b);
	}
	function isWriter() {
		return $this->has(self::WRITER);
	}
	function editor(bool $b = true) {
		$this->set(self::EDITOR, $b);
	}
	function isEditor() {
		return $this->has(self::EDITOR);
	}
	function fan(bool $b = true) {
		$this->set(self::FAN, $b);
	}
	function isFan() {
		return $this->has(self::FAN);
	}
	function customer(bool $b = true) {
		$this->set(self::CUSTOMER, $b);
	}
	function isCustomer() {
		return $this->has(self::CUSTOMER);
	}
	function client(bool $b = true) {
		$this->set(self::CLIENT, $b);
	}
	function isClient() {
		return $this->has(self::CLIENT);
	}
	function special(bool $b = true) {
		$this->set(self::SPECIAL, $b);
	}
	function isSpecial() {
		return $this->has(self::SPECIAL);
	}
	function VIP(bool $b = true) {
		$this->set(self::VIP, $b);
	}
	function isVIP() {
		return $this->has(self::VIP);
	}
	function POI(bool $b = true) {
		$this->set(self::POI, $b);
	}
	function isPOI() {
		return $this->has(self::POI);
	}
	function staff(bool $b = true) {
		$this->set(self::STAFF, $b);
	}
	function isStaff() {
		return $this->has(self::STAFF);
	}
	function moderator(bool $b = true) {
		$this->set(self::MODERATOR, $b);
	}
	function isModerator() {
		return $this->has(self::MODERATOR);
	}
	function administrator(bool $b = true) {
		$this->set(self::ADMINISTRATOR, $b);
	}
	function isAdministrator() {
		return $this->has(self::ADMINISTRATOR);
	}
	function owner(bool $b = true) {
		$this->set(self::OWNER, $b);
	}
	function isOwner() {
		return $this->has(self::OWNER);
	}
	function mod(bool $b = true) {
		return $this->moderator($b);
	}
	function isMod() {
		return $this->isModerator();
	}
	function admin(bool $b = true) {
		return $this->administrator($b);
	}
	function isAdmin() {
		return $this->isAdministrator();
	}
	function reset() {
		$this->flags = 0;
	}
	function data():object {
		return (object) [
			'value' => $this->value,
			'isBanned' => $this->isBanned(),
			'isSuspended' => $this->isSuspended(),
			'isBot' => $this->isBot(),
			'isGuest' => $this->isGuest(),
			'isMember' => $this->isMember(),
			'isCreator' => $this->isCreator(),
			'isArtist' => $this->isArtist(),
			'isActor' => $this->isActor(),
			'isWriter' => $this->isWriter(),
			'isEditor' => $this->isEditor(),
			'isFan' => $this->isFan(),
			'isCustomer' => $this->isCustomer(),
			'isClient' => $this->isClient(),
			'isSpecial' => $this->isSpecial(),
			'isVIP' => $this->isVIP(),
			'isPOI' => $this->isPOI(),
			'isStaff' => $this->isStaff(),
			'isModerator' => $this->isModerator(),
			'isAdministrator' => $this->isAdministrator(),
			'isOwner' => $this->isOwner(),
		];
	}
	function __debugInfo():array {
		return (array) $this->data();
	}
}

