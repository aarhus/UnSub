<?php

namespace Modules\UnSub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Conversation;
use App\Thread;
use GuzzleHttp\Client;



class UnSubController extends Controller
{

	const ACTION_TYPE_UNSUBSCRIBE = 167;
	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	public function index($mailbox_id, $conversation_id)
	{

		$conversation = \App\Conversation::findOrFail($conversation_id);

		if (!$conversation || !$conversation->id) {
			return redirect()->route('conversations.view', ['id' => $conversation_id,]);
		}

		$thread = $conversation->getFirstThread();

		$unsub = $thread->getMeta("List-Unsubscribe", null);
		$unsubPost = $thread->getMeta("List-Unsubscribe-Post", null);

		if (!$unsub) {

			$headers = $this->getMyHeaders($thread->headers);
			if (!isset($headers["List-Unsubscribe"])) {
				return redirect()->route('conversations.view', ['id' => $conversation_id,]);
			}

			$unsub = $headers["List-Unsubscribe"];
			$unsubPost = $headers["List-Unsubscribe-Post"] ?? null;
		}

		$opts = array_map(function ($x) {
			return trim($x, " \n\r\t\v\x00,<");
		}, explode(">", $unsub));

		$l = array_keys($opts);

		foreach ($l as $x) {
			$y = $opts[$x];
			unset($opts[$x]);

			if ($y) {
				$bits = explode(":", $y, 2);
				if (isset($bits[1])) {
					$opts[$bits[0]] = $bits[1];
				}
			}
		}

		if ((isset($opts["https"]) || isset($opts["http"]))) {

			$client = new Client();

			$url = isset($opts["https"]) ? 'https:' . $opts["https"] : 'http:' . $opts["http"];
			$response = null;

			if ("List-Unsubscribe=One-Click" === $unsubPost) {
				$response = $client->request('POST', $url, [
					'body' => "List-Unsubscribe=One-Click",
				]);
			} else {
				$response = $client->request('GET', $url);
			}


			$body = $response->getBody();

			$conversation->setMeta("List-Unsubscribe-Submitted", ["status" => $response->getStatusCode(), "reason" => $response->getReasonPhrase()]);

			$auth_user = auth()->user();
			$created_by_user_id = $auth_user->id;
			\App\Thread::create($conversation, Thread::TYPE_LINEITEM, '', [
				'user_id' => $conversation->user_id,
				'created_by_user_id' => $created_by_user_id,
				'action_type' => self::ACTION_TYPE_UNSUBSCRIBE,
				'source_via' => \App\Thread::PERSON_USER,
				'source_type' => \App\Thread::SOURCE_TYPE_WEB,
				'body' => $body,
				'meta' => [
					'code' => $response->getStatusCode(),
					'message' => $response->getReasonPhrase()
				]
			]);

			$conversation->save();
			return redirect()->route('conversations.view', ['id' => $conversation_id,]);


		}


		return redirect()->route('conversations.view', ['id' => $conversation_id,]);
	}

	/**
	 * Show the form for creating a new resource.
	 * @return Response
	 */
	public function create()
	{
		return view('unsub::create');
	}

	/**
	 * Store a newly created resource in storage.
	 * @param  Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
	}

	/**
	 * Show the specified resource.
	 * @return Response
	 */
	public function show()
	{
		return view('unsub::show');
	}

	/**
	 * Show the form for editing the specified resource.
	 * @return Response
	 */
	public function edit()
	{
		return view('unsub::edit');
	}

	/**
	 * Update the specified resource in storage.
	 * @param  Request $request
	 * @return Response
	 */
	public function update(Request $request)
	{
	}

	/**
	 * Remove the specified resource from storage.
	 * @return Response
	 */
	public function destroy()
	{
	}
	function getMyHeaders($h)
	{
		$h_array = explode("\n", $h);

		foreach ($h_array as $h) {

			print "Processing: $h<br />";
			$headers = [];
			// Check if row start with a char
			if (preg_match("/^[A-Z]/i", $h)) {

				$tmp = explode(":", $h, 2);
				$header_name = $tmp[0];
				$header_value = array_reduce(
					imap_mime_header_decode(trim($tmp[1])),
					function ($carry, $key) {
						return $carry . $key->text;
					},
					""
				);

				$headers[$header_name] = $header_value;

			} else {
				// Append row to previous field

				if (isset($header_name)) {


					$headers[$header_name] = array_reduce(
						imap_mime_header_decode(trim($h)),
						function ($carry, $key) {
							return $carry . $key->text;
						},
						$headers[$header_name] ?? []
					);
				}
			}

		}
		return $headers ?? [];
	}

}