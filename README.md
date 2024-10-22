**Configuración módulo Auditoría \- yii2-audit**

**En composer.json:**  
	
 	"repositories": [  
	       {  
	           "type": "vcs",  
	           "url": "git@github.com:innovacion-scpl/yii2-audit.git"  
	       }  
	   ]
	
	 "require": {  
	           "bedezign/yii2-audit": "dev-master"  
	  }  
o la rama más actualizada

**Copiar migraciones del módulo de vendor badezign/src/migrations/1.1x a console/migrations:**

	bedezign\yii2\audit\\migrations\m150626\_000001\_create\_audit\_entry  
	bedezign\yii2\\audit\migrations\m150626\_000002\_create\_audit\_data  
	bedezign\yii2\\audit\migrations\m150626\_000003\_create\_audit\_error  
	bedezign\yii2\\audit\migrations\m150626\_000004\_create\_audit\_trail  
	bedezign\yii2\\audit\migrations\m150626\_000005\_create\_audit\_javascript  
	bedezign\yii2\\audit\migrations\m150626\_000006\_create\_audit\_mail  
	bedezign\yii2\\audit\migrations\m150714\_000001\_alter\_audit\_data  
	bedezign\yii2\\audit\migrations\m170126\_000001\_alter\_audit\_mail

**Ejecutar ./yii migrate**

**Agregar a backend/config/main.php:**

    'modules' => [  
            	'audit' => 'bedezign\yii2\audit\Audit',  
     		'accessRoles' => ['Auditor'],  
    		'userIdentifierCallback' => ['common\models\User', 'userIdentifierCallback'],  
    	],

**En common/models/User.php agregar función:**

	public static function userIdentifierCallback($id)  
	   {  
	       $user = self::findOne($id);  
	       return $user ? Html::a($user->apellido.' '.$user->nombre, ['/user/view', 'id' => $user->id]) : $id;  
	   }

**Rastreo de cambios por modelo**

**Agregar al modelo que quiere ser rastreado:**

	public function behaviors()  
	   {  
	       return [  
	           'bedezign\yii2\audit\AuditTrailBehavior'  
	       ];  
	   }

**Crear Rol Auditor con permiso Auditar con las rutas audit/\* y asignarlo al usuario que quiera ver la sección Auditoría**

**Para ingresar la ruta es *nombreDelSistema*/audit/**



## License

BSD-3 - Please refer to the [license](https://github.com/bedezign/yii2-audit/blob/master/LICENSE.md).
![Analytics](https://ga-beacon.appspot.com/UA-65104334-3/yii2-audit/README.md?pixel)
