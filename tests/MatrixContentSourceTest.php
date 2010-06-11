<?php
/**

Copyright (c) 2009, SilverStripe Australia Limited - www.silverstripe.com.au
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the 
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software 
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, 
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY 
OF SUCH DAMAGE.
 
 */
 
class MatrixContentSourceTest extends SapphireTest
{
	public function testGetMatrixRepo()
	{
		$repo = $this->getMock('MatrixJSClient', array('isConnected', 'connect'));
		$source = new DummyMatrixContentSource();
		$source->setMockClient($repo);
		
		$config = array(
			'apiUrl' => 'http://path/to',
			'username' => 'username',
			'password' => 'password',
			'apiKey' => '12345',
		);
		
		$source->ApiUrl = $config['apiUrl'];
		$source->Username = $config['username'];
		$source->Password = $config['password'];
		$source->ApiKey = $config['apiKey'];
		
		// so we expect isConnected to be called, and connect to be called
		$repo->expects($this->once())
				->method('isConnected')
				->will($this->returnValue(false));

		$repo->expects($this->once())
				->method('connect')
				->with($this->equalTo($config));
				
		$matrixRepo = $source->getRemoteRepository();
	}
	
	public function testGetMatrixObject()
	{
		$repo = $this->getMock('MatrixJSClient', array('isConnected', 'connect', 'call'));
		$source = new DummyMatrixContentSource();
		$source->ID = 'TEST';
		$source->setMockClient($repo);

		// need this so that it returns false and creates our mock client for
		// subsequent calls
		$repo->expects($this->once())
				->method('isConnected')
				->will($this->returnValue(false));
				
		// call('getGeneral', array('id' => $id));
		// call('getAttributes', array('id'=>$id));
		$json = json_decode('{"name":"CONTENT NAME","short_name":"DIV Content","id":"53","type_code":"content_type_wysiwyg"}');
		$getAttr = json_decode('{"html":"Page contents ","name":"CONTENT NAME","htmltidy_status":"disabled","htmltidy_errors":""}');

		$repo->expects($this->atLeastOnce())
				->method('call')
				// ->with('getGeneral', array('id' => '1'))
				->will($this->onConsecutiveCalls($json, $getAttr));

		$object = $source->getObject('53');
		
		// make sure its name is something
		$this->assertEquals($object->Title, 'CONTENT NAME');
		$this->assertEquals($object->id, '53');
		$this->assertEquals($object->ID, 'TEST|53');
	}
	
	public function testGetObjectChildren()
	{
		$repo = $this->getMock('MatrixJSClient', array('isConnected', 'connect', 'call'));
		$source = new DummyMatrixContentSource();
		$source->ID = 'TEST';
		$source->setMockClient($repo);

		// need this so that it returns false and creates our mock client for
		// subsequent calls
		$repo->expects($this->atLeastOnce())
				->method('isConnected')
				->will($this->onConsecutiveCalls(false, true, true));
				
		// call('getGeneral', array('id' => $id));
		// call('getAttributes', array('id'=>$id));
		$json = json_decode('{"name":"CONTENT NAME","short_name":"DIV Content","id":"50","type_code":"content_type_wysiwyg"}');
		$getAttr = json_decode('{"html":"Page contents ","name":"CONTENT NAME","htmltidy_status":"disabled","htmltidy_errors":""}');
		$getChildren = json_decode('{"51":{"id":"51","name":"Page Contents","type_code":"bodycopy","link_id":"52"}}');
		$get51a = json_decode('{"name":"CHILD_NODE","short_name":"DIV Content","id":"51","type_code":"content_type_wysiwyg"}');
		$get51b = json_decode('{"html":"Page contents ","name":"CHILD_NODE","htmltidy_status":"disabled","htmltidy_errors":""}');
		
		$repo->expects($this->exactly(5))
				->method('call')
				->will($this->onConsecutiveCalls($json, $getAttr, $getChildren, $get51a, $get51b));

		$object = $source->getObject('50');
		$this->assertEquals($object->ID, 'TEST|50');
		// now call its get children method
		
		$children = $object->stageChildren();
		
		$this->assertEquals(1, $children->Count());
	}
}

class DummyMatrixContentSource extends MatrixContentSource
{
	private $mockClient;
	public function setMockClient($mock)
	{
		$this->mockClient = $mock;
	}
	
	public function createConnector($type)
	{
		return $this->mockClient; 
	}
}
?>