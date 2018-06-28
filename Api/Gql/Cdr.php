<?php

namespace FreePBX\modules\Cdr\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use GraphQL\Type\Definition\EnumType;

class Cdr extends Base {
	protected $module = 'cdr';

	public function queryCallback() {
		if($this->checkAllReadScope()) {
			return function() {
				return [
					'allCdrs' => [
						'type' => $this->typeContainer->get('cdr')->getConnectionType(),
						'description' => 'CDR Reports',
						'args' => array_merge(
							Relay::forwardConnectionArgs(),
							[
								'orderby' => [
									'type' => new EnumType([
										'name' => 'cdrOrderBy',
										'description' => 'Dispositions represent the final state of the call from the perspective of Party A',
										'values' => [
											'duration' => [
												'value' => 'duration',
												'description' => 'The channel was never answered. This is the default disposition for an unanswered channel.'
											],
											'date' => [
												'value' => 'timestamp',
												'description' => "The channel dialed something that was congested."
											]
										]
									]),
									'description' => 'The final known disposition of the CDR record',
									'defaultValue' => 'timestamp'
								]
							]
						),
						'resolve' => function($root, $args) {
							$after = !empty($args['after']) ? Relay::fromGlobalId($args['after'])['id'] : null;
							$before = !empty($args['before']) ? Relay::fromGlobalId($args['before'])['id'] : null;
							$first = !empty($args['first']) ? $args['first'] : null;
							$last = !empty($args['last']) ? $args['last'] : null;

							return Relay::connectionFromArraySlice(
								$this->getGraphQLCalls($after, $first, $before, $last, $args['orderby']),
								$args,
								[
									'sliceStart' => !empty($after) ? $after : 0,
									'arrayLength' => $this->getTotal()
								]
							);
						},
					],
					'cdr' => [
						'type' => $this->typeContainer->get('cdr')->getObject(),
						'args' => [
							'id' => [
								'type' => Type::id(),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							$id = Relay::fromGlobalId($args['id'])['id'];
							$record = $this->getRecordByID($id);
							return !empty($record) ? $record : null;
						}
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('cdr');
		$user->setDescription('Used to manage a system wide list of blocked callers');

		$user->setGetNodeCallback(function($id) {
			$record = $this->getRecordByID($id);
			return !empty($record) ? $record : null;
		});

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->addFieldCallback(function() {
			return [
				'id' => Relay::globalIdField('cdr', function($row) {
					return $row['uniqueid'];
				}),
				'uniqueid' => [
					'type' => Type::string(),
					'description' => 'A unique identifier for the Party A channel'
				],
				'calldate' => [
					'type' => Type::string(),
					'description' => 'The time the CDR was created'
				],
				'timestamp' => [
					'type' => Type::int(),
					'description' => 'The time the CDR was created'
				],
				'clid' => [
					'type' => Type::string(),
					'description' => 'The Caller ID with text'
				],
				'src' => [
					'type' => Type::string(),
					'description' => 'The Caller ID Number'
				],
				'dst' => [
					'type' => Type::string(),
					'description' => 'The destination extension'
				],
				'dcontext' => [
					'type' => Type::string(),
					'description' => 'The destination context'
				],
				'channel' => [
					'type' => Type::string(),
					'description' => 'The name of the Party A channel'
				],
				'dstchannel' => [
					'type' => Type::string(),
					'description' => 'The name of the Party B channel'
				],
				'lastapp' => [
					'type' => Type::string(),
					'description' => 'The last application the Party A channel executed'
				],
				'lastdata' => [
					'type' => Type::string(),
					'description' => 'The application data for the last application the Party A channel executed'
				],
				'duration' => [
					'type' => Type::int(),
					'description' => 'The time in seconds from start until end'
				],
				'billsec' => [
					'type' => Type::int(),
					'description' => 'The time in seconds from answer until end'
				],
				'disposition' => [
					'type' => new EnumType([
						'name' => 'dispositiontypes',
						'description' => 'Dispositions represent the final state of the call from the perspective of Party A',
						'values' => [
							'noanswer' => [
								'value' => 'NO ANSWER',
								'description' => 'The channel was never answered. This is the default disposition for an unanswered channel.'
							],
							'congestion' => [
								'value' => 'CONGESTION',
								'description' => "The channel dialed something that was congested."
							],
							'failed' => [
								'value' => 'FAILED',
								'description' => 'The channel attempted to dial but the call failed'
							],
							'busy' => [
								'value' => 'BUSY',
								'description' => "The channel attempted to dial but the remote party was busy"
							],
							'answered' => [
								'value' => 'ANSWERED',
								'description' => 'The channel was answered. When the channel is answered, the hangup cause no longer changes the disposition'
							]
						]
					]),
					'description' => 'The final known disposition of the CDR record'
				],
				'amaflags' => [
					'type' => new EnumType([
						'name' => 'amatypes',
						'description' => 'AMA Flags are set on a channel and are conveyed in the CDR. They inform billing systems how to treat the particular CDR. Asterisk provides no additional semantics regarding these flags - they are present simply to help external systems classify CDRs',
						'values' => [
							'omit' => [
								'value' => 'OMIT',
								'description' => ''
							],
							'billing' => [
								'value' => 'BILLING',
								'description' => ""
							],
							'documentation' => [
								'value' => 'DOCUMENTATION',
								'description' => ''
							]
						]
					]),
					'description' => 'A flag specified on the Party A channel'
				],
				'accountcode' => [
					'type' => Type::string(),
					'description' => 'An account code associated with the Party A channel'
				],
				'userfield' => [
					'type' => Type::string(),
					'description' => 'A user defined field set on the channels. If set on both the Party A and Party B channel, the userfields of both are concatenated and separated by a ;'
				],
				'did' => [
					'type' => Type::string(),
					'description' => 'The DID that was used to reach this destination'
				],
				'recordingfile' => [
					'type' => Type::string(),
					'description' => 'The recording file of this entry'
				],
				'cnum' => [
					'type' => Type::string(),
					'description' => 'The Caller ID Number'
				],
				'outbound_cnum' => [
					'type' => Type::string(),
					'description' => 'The Outbound Caller ID Number'
				],
				'outbound_cnam' => [
					'type' => Type::string(),
					'description' => 'The Outbound Caller ID Name'
				],
				'dst_cnam' => [
					'type' => Type::string(),
					'description' => 'The destination Caller ID Name'
				],
				'linkedid' => [
					'type' => Type::string(),
					'description' => 'Description of the blocked number'
				],
				'peeraccount' => [
					'type' => Type::string(),
					'description' => 'The account code of the Party B channel'
				],
				'sequence' => [
					'type' => Type::string(),
					'description' => 'A numeric value that, combined with uniqueid and linkedid, can be used to uniquely identify a single CDR record'
				],
			];
		});

		$user->setConnectionResolveNode(function ($edge) {
			return $edge['node'];
		});

		$user->setConnectionFields(function() {
			return [
				'totalCount' => [
					'type' => Type::int(),
					'description' => 'A count of the total number of objects in this connection, ignoring pagination. This allows a client to fetch the first five objects by passing "5" as the argument to "first", then fetch the total count so it could display "5 of 83", for example.',
					'resolve' => function($value) {
						return $this->getTotal();
					}
				],
				'cdrs' => [
					'type' => Type::listOf($this->typeContainer->get('cdr')->getObject()),
					'description' => 'A list of all of the objects returned in the connection. This is a convenience field provided for quickly exploring the API; rather than querying for "{ edges { node } }" when no edge data is needed, this field can be be used instead. Note that when clients like Relay need to fetch the "cursor" field on the edge to enable efficient pagination, this shortcut cannot be used, and the full "{ edges { node } }" version should be used instead.',
					'resolve' => function($root, $args) {
						$data = array_map(function($row){
							return $row['node'];
						},$root['edges']);
						return $data;
					}
				]
			];
		});
	}

	private function getTotal() {
		$sql = "SELECT count(*) as count FROM ".$this->freepbx->Cdr->getDbTable();
		$sth = $this->freepbx->Cdr->cdrdb->prepare($sql);
		$sth->execute();
		return $sth->fetchColumn();
	}

	private function getGraphQLCalls($after, $first, $before, $last, $orderby) {
		switch($orderby) {
			case 'duration':
				$orderby = 'duration';
			break;
			case 'date':
			default:
				$orderby = 'timestamp';
			break;
		}

		$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->freepbx->Cdr->getDbTable()." ORDER by $orderby DESC";
		$sql .= " " . (!empty($first) ? "LIMIT ".$first : "LIMIT 18446744073709551610");
		$sql .= " " . (!empty($after) ? "OFFSET ".$after : "OFFSET 0");
		$sth = $this->freepbx->Cdr->cdrdb->prepare($sql);
		$sth->execute();
		$calls = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $calls;
	}

	private function getRecordByID($rid) {
		$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->freepbx->Cdr->getDbTable()." WHERE uniqueid = :uid";
		$sth = $this->freepbx->Cdr->cdrdb->prepare($sql);
		try {
			$sth->execute(array("uid" => str_replace("_",".",$rid)));
			$recording = $sth->fetch(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return array();
		}
		return $recording;
	}
}
