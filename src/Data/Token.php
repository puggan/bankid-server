<?php
	/**
	 * Created by PhpStorm.
	 * User: puggan
	 * Date: 2018-06-09
	 * Time: 11:19
	 */

	namespace Puggan\BankIDServer\Data;

	use byrokrat\id\Exception\InvalidCheckDigitException;
	use byrokrat\id\Exception\InvalidDateStructureException;
	use byrokrat\id\Exception\InvalidStructureException;
	use byrokrat\id\PersonalId;
	use Puggan\BankIDServer\Exceptions\InternalError;
	use Puggan\BankIDServer\Exceptions\InvalidParameters;

	/** @noinspection PhpDocMissingThrowsInspection */

	/**
	 * Class Token
	 * @package Puggan\BankIDServer\Data
	 *
	 * @property string identification
	 * @property string token
	 * @property string auto_token
	 * @property string status
	 *
	 * @property int created_at
	 * @property int expires_at
	 * @property int fetched_at
	 *
	 * @property string hint
	 * @property mixed user
	 * @property mixed device
	 * @property mixed cert
	 * @property mixed signature
	 * @property mixed ocsp
	 *
	 * @property mixed[] loaded_data
	 */
	class Token
	{
		public const STATUS_PENDING = 'pending';
		public const STATUS_COMPLETE = 'complete';
		public const STATUS_FAILED = 'failed';

		public static $db_file = __DIR__ . '/../../db/tokens.db';

		public $identification;
		public $token;
		public $auto_token;
		public $status = self::STATUS_PENDING;
		public $created_at;
		public $expires_at;
		public $fetched_at;
		public $hint = 'outstandingTransaction';
		public $user;
		public $device;
		public $cert;
		public $signature;
		public $ocsp;

		private $loaded_data;

		/**
		 * Token constructor.
		 *
		 * @param string $token
		 * @param mixed[] $data
		 *
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		public function __construct($token, $data)
		{
			$this->token = $token;
			if(empty($this->token))
			{
				$this->identification = $data['personalNumber'] ?? NULL;
				$this->token = self::uuid();
				$this->auto_token = self::uuid();
				$this->created_at = time();
				$this->expires_at = $this->created_at + 30;
				$this->save();
			}
			else
			{
				$this->loaded_data = $data;
				foreach($this->loaded_data as $key => $value)
				{
					$this->$key = $value;
				}
			}
			$this->validate();
		}

		/**
		 * @throws InvalidParameters
		 * @throws InternalError
		 */
		public function validate()
		{
			if($this->status === self::STATUS_PENDING)
			{
				if($this->identification)
				{
					try
					{
						new PersonalId($this->identification);
					}
					catch(InvalidCheckDigitException $e)
					{
						$this->status = self::STATUS_FAILED;
						$this->hint = 'certificateErr';
						$this->save();
					}
					catch(InvalidDateStructureException $e)
					{
						throw new InvalidParameters('Felaktigt formaterat personnummer', 0, $e);
					}
					catch(InvalidStructureException $e)
					{
						throw new InvalidParameters('Felaktigt formaterat personnummer', 0, $e);
					}
				}
			}

			if($this->status === self::STATUS_COMPLETE)
			{
				if($this->identification[strlen($this->identification) - 2] & 1)
				{
					$name = ['Sven', 'Svensson'];
				}
				else
				{
					$name = ['Anna', 'Andersson'];
				}
				$this->user = [
					'personalNumber' => $this->identification,
					'name' => implode(' ', $name),
					'givenName' => $name[0],
					'surname' => $name[1],
				];
				$this->device = ['ipAddress' => '127.0.0.1'];
				$time_ms = time() * 1000;
				$this->cert = ['notBefore' => $time_ms - 3600000, 'notAfter' => $time_ms + 3600000];
				$this->signature = base64_encode("This has bean signed automaticly, and we don't need an encryption signature to prove that, just store this base64 data anyway");
				$this->ocsp = base64_encode("We didn't use any nonce, as we didn't use encryption, just store this base64 data anyway");
			}
		}

		/**
		 * @throws InternalError
		 */
		public function mark_fetched()
		{
			if($this->status !== self::STATUS_COMPLETE)
			{
				$this->fetched_at = time();
				$this->save();
			}
		}

		/**
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		public function cancel()
		{
			if($this->status !== self::STATUS_PENDING)
			{
				throw new InvalidParameters('Token already completed');
			}

			$this->status = self::STATUS_FAILED;
			$this->hint = 'userCancel';
			$this->fetched_at = time();
			$this->save();
		}

		/**
		 * @throws InternalError
		 */
		public function save()
		{
			if($this->loaded_data)
			{
				$updates = [];
				foreach($this->loaded_data as $key => $old_value)
				{
					if($this->$key !== $old_value)
					{
						$updates[$key] = $key . ' = :' . $key;
					}
				}
				if(!$updates)
				{

					return;
				}

				$db = self::db();
				$query = $db->prepare("UPDATE tokens SET " . implode(', ', $updates));
				foreach(array_keys($updates) as $key)
				{
					$query->bindValue($key, $this->$key);
				}
				if($query->execute() === FALSE)
				{
					$db->close();
					throw new InternalError('Failed to write to database');
				}

				$this->loaded_data = self::dbrow($this->token, $db);
				$db->close();
				return;
			}

			$db = self::db();
			$query = $db->prepare(
				'INSERT INTO tokens (id, auto_token, identification, status, hint, created_at, expires_at, fetched_at) VALUES (:id, :auto_token, :identification, :status, :hint, :created_at, :expires_at, NULL)'
			);
			$query->bindValue('id', $this->token);
			$query->bindValue('auto_token', $this->auto_token);
			$query->bindValue('identification', $this->identification);
			$query->bindValue('status', $this->status);
			$query->bindValue('created_at', $this->created_at);
			$query->bindValue('expires_at', $this->expires_at);
			$query->execute();

			$this->loaded_data = self::dbrow($this->token, $db);

			$db->close();
		}

		/**
		 * @param $token
		 *
		 * @return null|Token
		 * @throws InternalError
		 * @throws InvalidParameters
		 */
		static function find($token)
		{
			$row = self::dbrow($token);

			if(!$row)
			{
				return NULL;
			}

			$token = new self($token, $row);
			return $token;
		}

		/**
		 * @param string $token
		 *
		 * @param \SQLite3 $existing_db
		 *
		 * @return array|null
		 */
		static function dbrow($token, $existing_db = NULL)
		{
			if($existing_db)
			{
				$db = $existing_db;
			}
			else
			{
				try
				{
					$db = self::db(TRUE);
				}
				catch(InternalError $e)
				{
					return NULL;
				}
			}

			$query = $db->prepare('SELECT * FROM tokens WHERE id = :id');
			$query->bindValue('id', $token, SQLITE3_TEXT);
			$row = $query->execute()->fetchArray();
			if(!$existing_db)
			{
				$db->close();
			}
			return $row;
		}

		/**
		 * @param bool $read_only
		 *
		 * @return \SQLite3
		 * @throws InternalError
		 */
		static function db($read_only = FALSE)
		{
			try
			{
				if($read_only)
				{
					return new \SQLite3(self::$db_file, SQLITE3_OPEN_READONLY);
				}

				$db = new \SQLite3(self::$db_file);
			}
			catch(\Exception $e)
			{
				throw new InternalError('Failed to open database.' . PHP_EOL . $e->getMessage(), 0, $e);
			}

			$db->exec('CREATE TABLE IF NOT EXISTS tokens (id STRING, auto_token STRING, identification STRING, status STRING, hint STRING, created_at BIGINT, expires_at BIGINT, fetched_at BIGINT)');

			return $db;
		}

		/**
		 * @return string
		 */
		static function uuid()
		{
			$h = function ($n) {
				return bin2hex(random_bytes($n));
			};
			return implode('-', [$h(8), $h(4), $h(4), $h(4), $h(12)]);
		}
	}
