<?php

require_once('ITechTable.php');
require_once('Helper.php');

class Studentedit extends ITechTable
{
	protected $_primary = 'id';
	protected $_name = 'student';
	protected $_person = 'person';
	protected $_title = 'person_title_option';
	protected $_city = 'location_city';
	protected $_location = 'location';
	protected $_cadres = 'cadres';
	protected $_funding = 'link_student_funding';


	public function EditStudent($pupilid) {
		# MAKING MULTI DIMENSION OUTPUT FOR MULTIPLE TABLES
		$output = array();

		# GETTING BASIC PERSON RECORD
		$select = 'select person.*, person_title_option.title_phrase from person 
		    LEFT JOIN person_title_option on person.title_option_id=person_title_option.id 
		    where person.id=' . $pupilid;
		$row = $this->dbfunc()->fetchAll($select);
		$output['person'] = $row;

		# GETTING STUDENT RECORD
			//TA:#406
		$select = 'SELECT student.*, institution.institutionname, institution.address1 as inst_address1, 
		    institution.address2 as inst_address2, 
		    institution.city as inst_city, institution.postalcode as inst_postalcode, institution.phone as inst_phone, 
		    institution.fax as inst_fax, institution.email as inst_email, lookup_nationalities.nationality FROM student 
		    LEFT JOIN institution ON institution.id=student.institutionid 
		    LEFT JOIN lookup_nationalities on student.nationalityid=lookup_nationalities.id
		    WHERE personid = ' . $pupilid;
		$row = $this->dbfunc()->fetchAll($select);
		$output['student'] = $row;

		# GETTING FUNDING RECORD
		$select = $this->dbfunc()->select()
			->from($this->_funding)
			->where('studentid = ?',$output['student'][0]['id']);
		$row = $this->dbfunc()->fetchAll($select);
		$output['funding'] = $row;
                                                                                                            
		# GETTING COHORT LINK
		$select = $this->dbfunc()->select()
			->from('link_student_cohort')
			->join(array('c' => 'cohort'),
			    'c.id = link_student_cohort.id_cohort')
			->where('id_student = ?',$output['student'][0]['id']);
			//$row = $this->dbfunc()->fetchAll($select);
			//TA:#217 add to select: order by dropdate, joindate desc;
		$row = $this->dbfunc()->fetchAll($select . " order by dropdate, joindate desc ");
		$output['link_cohort'] = $row;

		# GETTING PERMANENT ADDRESS
		//TA:#489 add 'phone'
		$select = $this->dbfunc()->select()
			->from(array('a' => 'addresses'),
					array('address1','address2','city','postalcode','state','country','id_addresstype','id_geog1','id_geog2','id_geog3', 'phone'))
			->join(array('l' => 'link_student_addresses'),
					'a.id = l.id_address')
			->where('l.id_student = ?',$output['student'][0]['id'])
			->where('a.id_addresstype = 1');
		$row = $this->dbfunc()->fetchAll($select);
		$output['permanent_address'] = $row;

		# GETTING COHORT IF EXISTS
		if (count ($output['link_cohort']) > 0){
			$select = $this->dbfunc()->select()
				->from('cohort')
				->where('id = ?',$output['link_cohort'][0]['id']);
			$row = $this->dbfunc()->fetchAll($select);
			$output['cohort'] = $row;

			if (count ($row) > 0){
				# GETTING CADRE FROM COHORT
				$select = $this->dbfunc()->select()
					->from('cadres')
					->where('id = ?',$output['cohort'][0]['cadreid']);
				$row = $this->dbfunc()->fetchAll($select);
				$output['cadre'] = $row;
			} else {
				$output['cohort'] = array();
			}

		} else {
			# NO COHORT
			$output['cohort'] = array();

			# NO COHORT = NO CADRE
			$output['cadre'] = array();
		}

		// echo $select->__toString();
		return $output;

		/* $db = $this->dbfunc();
		$select=$db->query("select * from person where id = '$pupilid'");
		$row = $select->fetch($select);
		return $row;*/

	}

	public function ViewStudent($pupilid){
		$select = $this->dbfunc()->select()
			->from('student')
			->where('personid = ?',$pupilid);
		$row = $this->dbfunc()->fetchAll($select);
		return $row;
	}

	// RETRIEVING COHORTS
	public function ListCohort(){
		$select = $this->dbfunc()->select()
			->from('cohort');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}
	
	//TA:#385 RETRIEVING COHORTS only for particular institution
	public function ListCohortByInst($inst_id){
	    $select = $this->dbfunc()->select()
	    ->from('cohort')->where('institutionid=?', $inst_id);
	    $result = $this->dbfunc()->fetchAll($select);
	    return $result;
	}

	// RETRIEVING TUTORS FOR ADVISORS
	public function ListTutors($cohort_id = 0){
		$select = $this->dbfunc()->select()
			->from(array('p' => 'person'),
				array('id','first_name','last_name'))
			->join(array('t' => 'tutor'),
				'p.id = t.personid')
			->order('last_name')
			->order('first_name');

		if($cohort_id > 0){
			$select = $select
				->joinLeft(array('lti' => 'link_tutor_institution'), 'lti.id_tutor = t.id')
				->joinLeft(array('c' => 'cohort'), 'lti.id_institution = c.institutionid or t.institutionid = c.institutionid') // bugfix, link_trainer_institution does not seem to contain all trainer institutions, however there is this column institution ID on table trainer anyway. #TODO (orig not left joins either)
				->where("c.id = {$cohort_id}");
		}

		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	//For Lisiting Title
	public function ListTitle(){
		$select = $this->dbfunc()->select()
			->from('person_title_option');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function listCadre(){
		$select = $this->dbfunc()->select()
		->from('cadres')
		->order('cadrename');
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	//For Lisiting Selected  Title
	public function EditTitle($titleid){
		$select = $this->dbfunc()->select()
			->from($this->_title)
			->where('id = ?',$titleid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	//For Lisiting City
	public function ListCity(){
		$select = $this->dbfunc()->select()
			->from($this->_city);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function EditCity($cityid){
		$select = $this->dbfunc()->select()
			->from($this->_city)
			->where('id = ?',$cityid);
		$result = $this->dbfunc()->fetchAll($select);
		return $result;
	}

	public function AddCadre($param){
		$db = $this->dbfunc();
		$insert=array('cadrename'=>"$param[cadre]");

		$rowArray = $db->insert($this->_cadres,$insert);
		$id = $db->lastInsertId();
		return $id;
	}

	public function ViewCadre($cadreid){
		$select = $this->dbfunc()->select()
			->from('cadres')
			->where('id = ?',$cadreid);
		$row = $this->dbfunc()->fetchAll($select);
		return $row;
	}



	public function UpdateCadre($param){
		$db = $this->dbfunc();
		$data = array('id'=>$param['cadreid'],
			'cadrename'=>$param['cadre']
		);

		$db->update('cadres',$data,"id = '".$param['cadreid']."'");
		return $data;
	}

	public function UpdatePerson($param){

		$datepick=$param['dob'];
		if($datepick == ''){
		    $dobymd = null;
		}else{
		  $dobymd=date("Y-m-d",strtotime($datepick));
		}
		$db = $this->dbfunc();
		$data = array(
			"title_option_id"	=>	$param['title'],
			'first_name'		=>	$param['firstname'],
			'middle_name'		=>	$param['middlename'],
			'last_name'			=>	$param['lastname'],
			'gender'			=>	$param['gender'],
			'birthdate'			=>	$dobymd,
			'home_address_1'	=>	$param['localaddress1'],
			'home_address_2'	=>	$param['localaddress2'],
			'home_postal_code'	=>	$param['localpostalcode'],
			'home_city'			=>	$param['localcity'],
			'home_is_residential' => $param['localisresidential'],
			'email'				=>	$param['email'],
			'email_secondary'	=>	$param['email_secondary'],
			'phone_work'		=>	$param['localphone'],
			'national_id'		=>	$param['nationalid'],
			'phone_mobile'		=>	$param['localcell'],
			//TA: there is no 'phone_mobile_2' column in PERSON table -> link it 'phone_home' column 
			//'phone_mobile_2'	=>	$param['localcell2'],
			'phone_home'	=>	$param['localcell2'],
			'national_id'		=>	$param['nationalid'],
			'custom_field1'	=>	$param['custom_field1'], //TA: added 7/22/2014
			'custom_field2'	=>	$param['custom_field2'], //TA: added 7/22/2014
			'custom_field3'	=>	$param['custom_field3'], //TA: added 7/22/2014
			'marital_status'	=>	$param['marital_status'], //TA: added 7/22/2014
			'spouse_name'	=>	$param['spouse_name'], //TA: added 7/22/2014
		    'home_district'	=>	$param['home_district'],//TA:#489.2
		    'ta'	=>	$param['ta'],//TA:#489.2
		    'gvh'	=>	$param['gvh'],//TA:#489.2
			
		    'timestamp_updated' => $this->now_expr(), //TA:105
		    'modified_by' => Session::getCurrentUserId(),//TA:105
		    
			//'home_location_id'=>"$param[city]"
		);

		$db->update('person',$data,"id = '".$param['id']."'");
		return $data;
	}

	public function UpdateStudentCohort($param){
		$db = $this->dbfunc();
		// GETTING STUDENT RECORD
		$select = $this->dbfunc()->select()
			->from('student')
			->where('personid = ?',$param['id']);
		$row = $this->dbfunc()->fetchAll($select);
		$studentid = $row[0]['id'];

		# DETERMINING VALUES TO USE FOR JOIN/DROP DATA

		# CHECKING ON SEPARATION DATE SELECTION
		if ((isset ($param['separationdate'])) && (trim($param['separationdate']) != "")){
			# DATE IS SET, CONVERTING TO PROPER FORMAT
			$dropdate = date("Y-m-d", strtotime($param['separationdate']));
		} else {
			# DATE NOT SET
			$dropdate = "0000-00-00";

			# IF NO DROP DATE, NO DROP REASON CAN EXIST EITHER
			$param['separationreason'] = 0;
		}

		# CHECKING FOR JOIN DATES NEXT
		if ($param['cohortid'] != 0){
			# A COHORT HAS BEEN SELECTED - WE ARE USING THE DATES OF IT TO UPDATE THE COHORT > STUDENT LINK

			# RETRIEVING COHORT
			$select = $this->dbfunc()->select()
				->from('cohort')
				->where('id = ?',$param['cohortid']);
			$row = $this->dbfunc()->fetchAll($select);
			$cohort = $row[0];
			$joindate = $cohort['startdate'];
		} else {
			# NO COHORT SELECTED - JOIN DATE RESET TO BLANK
			$joindate = "0000-00-00";
		}

		if ((isset($param['separationreason'])) && (!is_numeric($param['separationreason'])) && (trim($param['separationreason']) != "")){
			# ADDED A NEW INSTITUTE TYPE
			$insert = array (
				'reason' => $param['separationreason'],
				'reasontype' => 'drop',
			);
			$insertresult = $db->insert('lookup_reasons',$insert);
			$id = $db->lastInsertId();
			$param['separationreason'] = $id;
		}

		if ((isset($param['enrollmentreason'])) && (!is_numeric($param['enrollmentreason'])) && (trim($param['enrollmentreason']) != "")){
			# ADDED A NEW INSTITUTE TYPE
			$insert = array (
				'reason' => $param['enrollmentreason'],
				'reasontype' => 'join',
			);
			$insertresult = $db->insert('lookup_reasons',$insert);
			$id = $db->lastInsertId();
			$param['enrollmentreason'] = $id;
		}

		
		# DETERMINING IF A LINK ALREADY EXISTS BETWEEN STUDENT AND A COHORT
		$select = $this->dbfunc()->select()
			->from('link_student_cohort')
			->where('id_student = ?',$studentid);
		$result = $this->dbfunc()->fetchAll($select);
		
		if (count ($result) == 0){  
		    # LINK DOES NOT EXIST YET OR ADD aANOTHER COHORT
		 
		    
			$link = array(
				'id_cohort'			=>	$param['cohortid'],
			    'id_student'		=>	$studentid,
			    'joindate'			=>	$joindate,
			    'dropdate'			=>	$dropdate,
			    'joinreason'		=>	($param['enrollmentreason'] ? $param['enrollmentreason'] : 0),
			    'dropreason'		=>	($param['separationreason'] ? $param['separationreason'] : 0),
			);

			$rowArray = $db->insert("link_student_cohort",$link);
			$id = $db->lastInsertId();

			$helper = new helper();
			$helper->updatePersonInstitution("student",$studentid,$param['cohortid']);

			return $id;
		} else {
		    //TA:#217 try to find existing cohort
		    for ($k=0; $k<count ($result); $k++){
		        if($result[$k]['id_cohort'] == $param['cohortid']){
		            $linkid = $result[$k]['id'];
		            break;
		        }
		    }
		
		    if($linkid){
			 // UPDATING ADDRESS ROW
			 $db = $this->dbfunc();
			 $link = array(
			     'id_cohort'			=>	$param['cohortid'],
			     'joindate'			=>	$joindate,
			     'dropdate'			=>	$dropdate,
			     'joinreason'		=>	$param['enrollmentreason'],
			     'dropreason'		=>	$param['separationreason'],
			 );
			 if($dropdate === "0000-00-00"){
			     $link = array(
			         'id_cohort'			=>	$param['cohortid'],
			         'joindate'			=>	$joindate,
			         'joinreason'		=>	$param['enrollmentreason'],
			         'dropreason'		=>	$param['separationreason'],
			     );
			     
			 }
			
			 $helper = new helper();
			 $helper->updatePersonInstitution("student",$studentid,$param['cohortid']);
			 $db->update('link_student_cohort',$link,"id = '".$linkid."' AND id_student = " . $studentid);
			 return $link;
		    }else{//TA:#217 add another cohort

		        $link = array(
		            'id_cohort'			=>	$param['cohortid'],
		            'id_student'		=>	$studentid,
		            'joindate'			=>	$joindate,
		            'dropdate'			=>	$dropdate,
		            'joinreason'		=>	($param['enrollmentreason'] ? $param['enrollmentreason'] : 0),
		            'dropreason'		=>	($param['separationreason'] ? $param['separationreason'] : 0),
		        );
		        
		        $rowArray = $db->insert("link_student_cohort",$link);
		        $id = $db->lastInsertId();
		        
		        $helper = new helper();
		        $helper->updatePersonInstitution("student",$studentid,$param['cohortid']);
		        
		        return $id;
		    }
		}

	}

	public function UpdatePriorEducationTranscript($params) {
	    $add_courses = json_decode($params['prior_courses_to_add'], true);
	    $delete_courses = json_decode($params['prior_courses_to_delete']);

	    $db = $this->dbfunc();

        foreach ($delete_courses as $assoc_id) {
            $db->delete('student_prior_education', $db->quoteInto('id = ?', $assoc_id));
        }

	    foreach ($add_courses as $course_id => $course_grade) {
	        $db->insert('student_prior_education', array('student_id' => $params['sid'],
                'lookup_prior_education_courses_id' => $course_id, 'course_grade' => $course_grade));
        }
    }

	public function UpdateStudent($param){

		$db = $this->dbfunc();
		$enroll = $param['enrollmentdate'];
		$separate = $param['separationdate'];
		$enrolldate = date("Y-m-d",strtotime($enroll));

		if (trim($separate) != ""){
			$separatedate = date("Y-m-d",strtotime($separate));
		} else {
			$separatedate = "0000-00-00";
		}

		if ((isset($param['studenttype'])) && (!is_numeric($param['studenttype'])) && (trim($param['studenttype']) != "")){
			# ADDED A NEW INSTITUTE TYPE
			$insert = array (
				'studenttype' => $param['studenttype'],
			);
			$insertresult = $db->insert('lookup_studenttype',$insert);
			$id = $db->lastInsertId();
			$param['studenttype'] = $id;
		}

		# LOCAL GEO
		$param1 = $param['local-geo1'] ? $param['local-geo1'] : 0;
		$param2 = $param['local-geo2'] ? $param['local-geo2'] : 0;
		if (strpos($param2, "_")){
			$param2 = explode("_", $param2);
			$param2 = $param2[count($param2) - 1];
		}
		$param3 = $param['local-geo3'] ? $param['local-geo3'] : 0;
		if (strpos($param3, "_")){
			$param3 = explode("_", $param3);
			$param3 = $param3[count($param3) - 1];
		}

		# POST SCHOOL GEO
		$param4 = $param['postgeo1'] ? $param['postgeo1'] : 0;
		$param5 = $param['postgeo2'] ? $param['postgeo2'] : 0;
		if (strpos($param5, "_")){
			$param5 = explode("_", $param5);
			$param5 = $param5[count($param5) - 1];
		}
		$param6 = $param['postgeo3'] ? $param['postgeo3'] : 0;
		if (strpos($param6, "_")){
			$param6 = explode("_", $param6);
			$param6 = $param6[count($param6) - 1];
		}
		
		//TA: added 7/17/2014
		$hscomldate=date("Y-m-d", strtotime($param['hscomldate']));
		$schoolstartdate=date("Y-m-d", strtotime($param['schoolstartdate']));

		$student = array(//'nationalid'=>"$param[nationalid]",
			//'nationality'=>"$param[nationality]",
			//'studenttype'=>"$param[studenttype]",
			'personid'			=>	$param['id'],
			'studentid'			=>	$param['studentid'],
		    'index_number'			=>	$param['index_number'],//TA:#400
			'nationalityid'		=>	$param['nationality'],
			'studenttype'		=>	$param['studenttype'],
			'isgraduated'		=>	$param['graduated'],
			'yearofstudy'		=>	$param['yearofstudy'],
			'advisorid'			=>	$param['tutoradvisor'],
			'geog1'				=>	$param1,
			'geog2'				=>	$param2,
			'geog3'				=>	$param3,
		    'cadre'				=>	$param['cadre'] ? $param['cadre'] : 0,
		    'comments'			=>	$param['comments'] ? $param['comments'] : '',
			'postgeo1'			=>	$param4,
			'postgeo2'			=>	$param5,
			'postgeo3'			=>	$param6,
		    'postaddress1'		=>	$param['postaddress1'] ? $param['postaddress1'] : '',
			'postfacilityname'	=>	$param['postfacilityname'],
			'hscomldate'	=>	$hscomldate, //TA: added 7/17/2014
		    'lastinstatt'	=>	$param['lastinstatt'] ? $param['lastinstatt'] : '', //TA: added 7/17/2014
			'schoolstartdate' => $schoolstartdate, //TA: added 7/17/2014
		    'equivalence'	=>	$param['equivalence'] ? $param['equivalence'] : 0, //TA: added 7/17/2014
		    'lastunivatt'	=>	$param['lastunivatt'] ? $param['lastunivatt'] : '', //TA: added 7/17/2014
		    'personincharge'	=>	$param['personincharge'] ? $param['personincharge'] : '', //TA: added 7/17/2014
		    'emergcontact'	=>	$param['emergcontact'] ? $param['emergcontact'] : '', //TA: added 7/18/2014
		);

		$db->update('student',$student,"personid = '".$param['id']."'");
		$db->getProfiler()->setEnabled(true);
		return $student;
	}

	public function UpdatePermanentAddress($param){

		// GETTING STUDENT RECORD
		$select = $this->dbfunc()->select()
			->from('student')
			->where('personid = ?',$param['id']);
		$row = $this->dbfunc()->fetchAll($select);
		$studentid = $row[0]['id'];

		// DETERMINING IF A LINK EXISTS
		$select = $this->dbfunc()->select()
			->from('link_student_addresses')
			->where('id_student = ?',$studentid);
		$result = $this->dbfunc()->fetchAll($select);

		$param1 = $param['permanent-geo1'] ? $param['permanent-geo1'] : 0;
		$param2 = $param['permanent-geo2'] ? $param['permanent-geo2'] : 0;
		if (strpos($param2, "_")){
			$param2 = explode("_", $param2);
			$param2 = $param2[count($param2) - 1];
		}
		$param3 = $param['permanent-geo3'] ? $param['permanent-geo3'] : 0;
		if (strpos($param3, "_")){
			$param3 = explode("_", $param3);
			$param3 = $param3[count($param3) - 1];
		}
		

		//TA:15: fixed bug to edit student info (add address in DB is not NULL constrain)
		if (count ($result) == 0){
			# ADDING ADDRESS RECORD
			$db = $this->dbfunc();
			$address = array(
				'id_addresstype'	=>	1,
				'address1'			=>	$param['permanent-address1'] ? $param['permanent-address1'] : "",
				'address2'			=>	$param['permanent-address2'] ? $param['permanent-address2'] : "",
				'city'				=>	$param['permanent-city'] ? $param['permanent-city'] : "",
				'postalcode'		=>	$param['permanent-postalcode'] ? $param['permanent-postalcode'] : "",
				'id_geog1'			=>	$param1,
				'id_geog2'			=>	$param2,
				'id_geog3'			=>	$param3,
			    'phone'			=>	$param['permanent-phone'] ? $param['permanent-phone'] : "",//TA:#489
			);

			$rowArray = $db->insert("addresses",$address);
			$id = $db->lastInsertId();

			# LINKING TO STUDENT RECORD
			$db = $this->dbfunc();
			$linkrec = array(
				'id_address'		=>	$id,
				'id_student'		=>	$studentid,
			    'kin_name'		=>	$param['kin_name'],//TA:#489
			    'kin_relationship'		=>	$param['kin_relationship'],//TA:#489
			);
			$rowArray = $db->insert("link_student_addresses",$linkrec);
			$id = $db->lastInsertId();
			return $address;
		} else {
			// LINK EXISTS - UPDATE ADDRESS

			// RETRIEVING LINK ROW
			$row = $result[0];
			$addressid = $row['id_address'];

			$db = $this->dbfunc();
			$profiler = $db->getProfiler();
			$profiler->setEnabled(true);

			// UPDATING ADDRESS ROW
			$address = array(
			    'address1'			=>	$param['permanent-address1'] ? $param['permanent-address1'] : "",
			    'address2'			=>	$param['permanent-address2'] ? $param['permanent-address2'] : "",
			    'city'				=>	$param['permanent-city'] ? $param['permanent-city'] : "",
			    'postalcode'		=>	$param['permanent-postalcode'] ? $param['permanent-postalcode'] : "",
				'id_geog1'			=>	$param1,
				'id_geog2'			=>	$param2,
				'id_geog3'			=>	$param3,
			    'phone'			=>	$param['permanent-phone'] ? $param['permanent-phone'] : "",//TA:#489
			);

			$db->update('addresses',$address,"id = '".$addressid."' AND id_addresstype = 1");
			
			//TA:#489
			$db = $this->dbfunc();
			$linkrec = array(
			    'kin_name'		=>	$param['kin_name'],
			    'kin_relationship'		=>	$param['kin_relationship'],
			);
			$rowArray = $db->update("link_student_addresses",$linkrec, "id_student=" . $studentid);
			///

			return $address;
		}
	}
	
	//TA:#388
	public function UpdateFundingWithYear($params){
	    $db = $this->dbfunc();
	    if($params['funding_to_delete']){
	        $delete_funding = explode("|", $params['funding_to_delete']);
	        foreach ($delete_funding as $assoc_id) {
	            $db->delete('link_student_funding', $db->quoteInto('id = ?', $assoc_id));
	        }
	    }
	    if($params['funding_to_add']){
	       $add_funding = explode("|", $params['funding_to_add']);  
	       foreach ($add_funding as $id => $fundarr) {
	        $fund = explode("=", $fundarr);
	        $db->insert('link_student_funding', array('studentid' => $params['sid'],
	            'fundingsource' => $fund[1], 'fundingamount' => $fund[3], 'fundingyear' => $fund[0]));
	       }
	    }
	}

	public function UpdateFunding($param){
		// GETTING STUDENT RECORD
		$select = $this->dbfunc()->select()
			->from('student')
			->where('personid = ?',$param['id']);
		$row = $this->dbfunc()->fetchAll($select);
		$studentid = $row[0]['id'];


		if (!isset ($param['funding'])){
			# DELETE ALL ENTRIES
			$query = "DELETE FROM link_student_funding WHERE studentid  = " . $studentid;
			$this->dbfunc()->query($query);
		} else {
			# ADD / UPDATE ENTRIES
			$ids = array();
			foreach ($param['funding'] as $key=>$value){
				if ($param['fundingamount'][$key] != ""){
					$ids[] = $key;

					$query = "SELECT * FROM link_student_funding WHERE studentid = " . $studentid . " AND fundingsource = " . $key;
					$stmt = $this->dbfunc()->query($query);
					$result = $stmt->fetchAll();

					if (count ($result) > 0){
						$row = $result[0];
						$query = "UPDATE link_student_funding SET fundingamount = " . $param['fundingamount'][$key] . " WHERE id = " . $row['id'];
						$this->dbfunc()->query($query);
					} else {
						$query = "INSERT INTO link_student_funding SET studentid = " . $studentid . ", fundingsource = " . $key . ", fundingamount = '" . $param['fundingamount'][$key] . "'";
						$this->dbfunc()->query($query);
					}
				}
			}

			if (count ($ids) > 0){
				# REMOVING OTHER ENTRIES
				$query = "DELETE FROM link_student_funding WHERE studentid = " . $studentid . " AND fundingsource NOT IN (" . implode(",",$ids) . ")";
				$this->dbfunc()->query($query);
			}
		}
	}

    /**
     *
     * returns an array of the student's course names and grades from their education prior to entry in
     * their pre-service program
     *
     * @param $studentid
     *
     * @return array|null
     */
	public function getStudentPriorEducation($studentid) {
        $db = $this->dbfunc();
        $q = $db->select()
            ->from('student_prior_education', array())
            ->joinInner('lookup_prior_education_courses', 'lookup_prior_education_courses.id = student_prior_education.lookup_prior_education_courses_id', array())
            ->columns(array('student_prior_education.id', 'lookup_prior_education_courses.course_name', 'student_prior_education.course_grade'))
            ->where('student_prior_education.student_id = ?', $studentid)
            ->order('lookup_prior_education_courses.course_name');

        return $db->fetchAll($q);
    }

    /**
     * adds courses and grades to a student's prior education history
     *
     * @param $studentid
     * @param $courseids
     * @param $grades
     * @return null
     * @throws Exception
     */
    public function addStudentPriorEducation($studentid, $courseids, $grades) {
        $db = $this->dbfunc();
        if (!is_array($courseids) && !is_array($grades)) {
            $courseids = array($courseids);
            $grades = array($grades);
        }
        if (count($courseids) != count($grades)) {
            throw new Exception('The number of course IDs must match the number of grades.');
        }
        $i = 0;
        $numentries = count($courseids);
        while ($i < $numentries) {
            $db->insert('student_prior_education', array('student_id' => $studentid,
                'lookup_prior_education_courses_id' => $courseids[$i], 'course_grade' => $grades[$i]));
            $i++;
        }
        return null;
    }

    /**
     * deletes courses from a student's prior education history
     *
     * @param $studentid
     * @param $courseids
     * @return null
     */
    public function deleteStudentPriorEducation($studentid, $courseids) {
        $db = $this->dbfunc();
        if (!is_array($courseids)) {
            $courseids = array($courseids);
        }
        foreach ($courseids as $id) {
            $db->delete('student_prior_education', array(
                $db->quoteInto('student_id = ?', $studentid),
                $db->quoteInto('lookup_prior_education_courses_id = ?', $id)
            ));
        }
        return null;
    }

	public function addfunding($param) {
		$db = $this->dbfunc();
		$fundingsource = $param['fundingsource'];
		$fundingamount = $param['amount'];

		$funding = array(
			'fundingsource'=>$fundingsource,
		    'fundingamount'=>$fundingamount,
			'studentid'=>$param[studentid]);

		//print_r($funding);
		$rowArray = $db->insert($this->_funding,$funding);
		$id = $db->lastInsertId();
		return $id;
	}

	public function getStudentFunding($sid){
//TA:#388
		$select = "select link_student_funding.*, lookup_fundingsources.fundingname from link_student_funding 
		    left join lookup_fundingsources on lookup_fundingsources.id=link_student_funding.fundingsource where studentid =" . $sid;
		$result = $this->dbfunc()->fetchAll($select);
		return $result;

	}
	
//TA:51 10/05/2015
	public function DeleteStudent($param){
	    $person_id = $param['id'];
	    $student_primary_id = $param['sid'];
	    
	    // remove student
	    $sql = "DELETE FROM student WHERE personid = {$person_id}";
	    $result = $this->dbfunc()->query($sql);
	    
	    // set person as deleted
	    $sql = "UPDATE person SET is_deleted=1 where id = {$person_id}";
	    $result = $this->dbfunc()->query($sql);
	
	    // remove student cohort link
	    $sql = "DELETE FROM link_student_cohort WHERE id_student = {$student_primary_id}";
	    $result = $this->dbfunc()->query($sql);
	    
	    // remove student classes link
	    $sql = "DELETE FROM link_student_classes WHERE studentid = {$student_primary_id}";
	    $result = $this->dbfunc()->query($sql);
	    
	    // remove student practicums link
	    $sql = "DELETE FROM link_student_practicums WHERE studentid = {$person_id}";
	    $result = $this->dbfunc()->query($sql);
	    
	    // remove student licenses link
	    $sql = "DELETE FROM link_student_licenses WHERE studentid = {$student_primary_id}";
	    $result = $this->dbfunc()->query($sql);
	
	    // remove student licenses link
	    $sql = "DELETE FROM link_student_funding WHERE studentid = {$student_primary_id}";
	    $result = $this->dbfunc()->query($sql);
	    
	    // remove student licenses link
	    $sql = "DELETE FROM link_student_addresses WHERE id_student = {$student_primary_id}";
	    $result = $this->dbfunc()->query($sql);
	    
	    return true;
	}

 }

?>