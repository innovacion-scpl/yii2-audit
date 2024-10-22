Configuración módulo Auditoría - yii2-audit

En composer.json:
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

Copiar migraciones del módulo de vendor badezign/src/migrations/1.1x a console/migrations:

bedezign\yii2\audit\migrations\m150626_000001_create_audit_entry
	bedezign\yii2\audit\migrations\m150626_000002_create_audit_data
	bedezign\yii2\audit\migrations\m150626_000003_create_audit_error
	bedezign\yii2\audit\migrations\m150626_000004_create_audit_trail
	bedezign\yii2\audit\migrations\m150626_000005_create_audit_javascript
	bedezign\yii2\audit\migrations\m150626_000006_create_audit_mail
	bedezign\yii2\audit\migrations\m150714_000001_alter_audit_data
	bedezign\yii2\audit\migrations\m170126_000001_alter_audit_mail

Ejecutar ./yii migrate

Agregar a backend/config/main.php:

    'modules' => [
            	'audit' => 'bedezign\yii2\audit\Audit',
     			'accessRoles' => ['Auditor'],
    			'userIdentifierCallback' => ['common\models\User', 'userIdentifierCallback'],
    	],






En common/models/User.php agregar función:

public static function userIdentifierCallback($id)
   {
       $user = self::findOne($id);
       return $user ? Html::a($user->apellido.' '.$user->nombre, ['/user/view', 'id' => $user->id]) : $id;
   }

Rastreo de cambios por modelo

Agregar al modelo que quiere ser rastreado:

public function behaviors()
   {
       return [
           'bedezign\yii2\audit\AuditTrailBehavior'
       ];
   }


Crear Rol Auditor con permiso Auditar con las rutas audit/* y asignarlo al usuario que quiera ver la sección Auditoría

Para ingresar la ruta es nombreDelSistema/audit/

