<?php

/**
 * Short description for file
 *
 * Long description for file (if any)...
 *
 * PHP version 7
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Original Author <author@example.com>
 * @author     Another Author <another@example.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      File available since Release 1.2.0
 * @deprecated File deprecated in Release 2.0.0
 */

namespace Modules\UnSub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Conversation;
use App\Thread;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * Please use them in the order they appear here.  phpDocumentor has
 * several other tags available, feel free to use them.
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Original Author <author@example.com>
 * @author     Another Author <another@example.com>
 * @copyright  1997-2005 The PHP Group
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.2.0
 * @deprecated Class deprecated in Release 2.0.0
 */

class UnSubController extends Controller
{

    const ACTION_TYPE_UNSUBSCRIBE = 167;
    /**
     * Display a listing of the resource.
     *
     * @param int $mailbox_id      an integer of the mailbox_id
     * @param int $conversation_id an integer of how many problems happened.
     *
     * @return Response (or redirect)
     **/
    public function index($mailbox_id, $conversation_id)
    {

        $conversation = \App\Conversation::findOrFail($conversation_id);

        if (!$conversation || !$conversation->id) {
            return redirect()->route(
                'conversations.view',
                [
                    'id' => $conversation_id,
                ]
            );
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

        $opts = array_map(
            function ($x) {
                return trim($x, " \n\r\t\v\x00,<");
            },
            explode(">", $unsub)
        );

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

                $client = new Client(['request.options' => ['exceptions' => false,]]);

                $url = isset($opts["https"]) ? 'https:' . $opts["https"] : 'http:' . $opts["http"];
                $response = null;

            try {
                if ($unsubPost) {
                    $response = $client->request(
                        'POST',
                        $url,
                        [
                            'body' => "List-Unsubscribe=One-Click",
                        ]
                    );
                } else {
                    $response = $client->request('GET', $url);
                }

                $code = $response->getStatusCode();


                $body = $response->getBody();

                $conversation->setMeta("List-Unsubscribe-Submitted", ["status" => $response->getStatusCode(), "reason" => $response->getReasonPhrase()]);

                $auth_user = auth()->user();
                error_log($auth_user);
                $created_by_user_id = (gettype($auth_user) == "object") ? $auth_user->id : $auth_user ;
                \App\Thread::create(
                    $conversation, Thread::TYPE_LINEITEM,
                    '',
                    [
                        'user_id' => $conversation->user_id,
                        'created_by_user_id' => $created_by_user_id,
                        'action_type' => self::ACTION_TYPE_UNSUBSCRIBE,
                        'source_via' => \App\Thread::PERSON_USER,
                        'source_type' => \App\Thread::SOURCE_TYPE_WEB,
                        'body' => $body,
                        'meta' => [
                            'code' => $code,
                            'message' => $response->getReasonPhrase()
                        ]
                    ]
                );

                if ($code>=200 && $code <=299) {
                    $conversation->changeStatus(Conversation::STATUS_CLOSED, $auth_user, false);
                }
        
            } catch (ConnectException $e) {

                $auth_user = auth()->user();
                $created_by_user_id = (gettype($auth_user) == "object") ? $auth_user->id : $auth_user ;
                \App\Thread::create(
                    $conversation, Thread::TYPE_LINEITEM,
                    '',
                    [
                        'user_id' => $conversation->user_id,
                        'created_by_user_id' => $created_by_user_id,
                        'action_type' => self::ACTION_TYPE_UNSUBSCRIBE,
                        'source_via' => \App\Thread::PERSON_USER,
                        'source_type' => \App\Thread::SOURCE_TYPE_WEB,
                        'body' => "Something went wrong. ".print_r([ 'url' => $url, 'opts' => $opts, ]),
                        'meta' => [
                            'code' => 501,
                            'message' => "Sorry, something went wrong: " . $e->getMessage(),
                            'lineNumber' => $e->getLine(),
                    'trace'=>$e->getTraceAsString(),
                    'method' => ($unsubPost) ? 'POST' : "GET",
                    'url' => $url,
                    'opts' => $opts,
                        ]
                    ]
                );
            } catch (\Exception $e) {

                $auth_user = auth()->user();
                $created_by_user_id = (gettype($auth_user) == "object") ? $auth_user->id : $auth_user ;
                \App\Thread::create(
                    $conversation, Thread::TYPE_LINEITEM,
                    '',
                    [
                        'user_id' => $conversation->user_id,
                        'created_by_user_id' => $created_by_user_id,
                        'action_type' => self::ACTION_TYPE_UNSUBSCRIBE,
                        'source_via' => \App\Thread::PERSON_USER,
                        'source_type' => \App\Thread::SOURCE_TYPE_WEB,
                        'body' => "Something went wrong",
                        'meta' => [
                            'code' => 501,
                            'message' => "Sorry, something went wrong: " . $e->getMessage(),
                            'lineNumber' => $e->getLine(),
                    'trace'=>$e->getTraceAsString(),
                    'method' => ($unsubPost) ? 'POST' : "GET",
                    'user' => json_encode($auth_user),
                    'url' => $url,
                    'opts' => $opts,
                        ]
                    ]
                );
            }
            $conversation->save();
            return redirect()->route('conversations.view', ['id' => $conversation_id,]);


        }


        return redirect()->route('conversations.view', ['id' => $conversation_id,]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('unsub::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request not used
     *
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     *
     * @return Response
     */
    public function show()
    {
        return view('unsub::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        return view('unsub::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request not used
     *
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy()
    {
    }


    /**
     * Wibble oww
     *
     * @param string $h string of headers
     *
     * @return array of strings...
     * @throws exceptionclass [description]
     *
     * @since      Method available since Release 1.2.0
     * @deprecated Method deprecated in Release 2.0.0
     */
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
