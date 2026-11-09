
'use strict';
const fs=require('fs'),path=require('path'),crypto=require('crypto'),cp=require('child_process'),net=require('net'),assert=require('assert/strict');
const ROOT=path.resolve(__dirname,'../..'),RUNTIME=path.join(ROOT,'.runtime'),BIN=path.join(RUNTIME,'mariadb/binarios/bin'),DATA=path.join(RUNTIME,'mariadb/data');
const PHP=process.env.GA8_PHP||'C:/xampp/php/php.exe',PORT=13348,HTTP=18088,DB='don_pan_ga8_ev01_pruebas';
if(ROOT.toLowerCase().replaceAll('\\','/')!=='c:/sena/don-pan-ga8-aa1-ev01-lab')throw Error('Ejecutor restringido al laboratorio aprobado.');
function safe(p){let a=path.resolve(p);while(true){if(fs.existsSync(a)&&(fs.lstatSync(a).isSymbolicLink()||fs.realpathSync.native(a).toLowerCase()!==a.toLowerCase()))throw Error('Redireccion');let b=path.dirname(a);if(a===b)break;a=b}}
safe(ROOT);safe(DATA);safe(PHP);
function readSecret(name){const script="$s=Get-Content -LiteralPath $env:GA8_SECRET_FILE -Raw; $v=ConvertTo-SecureString $s; $p=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($v); try{[Console]::Write([Runtime.InteropServices.Marshal]::PtrToStringBSTR($p))}finally{[Runtime.InteropServices.Marshal]::ZeroFreeBSTR($p)}";return cp.execFileSync('powershell.exe',['-NoProfile','-Command',script],{env:{...process.env,GA8_SECRET_FILE:path.join(RUNTIME,'seguridad',name+'.dpapi')},encoding:'utf8',windowsHide:true}).trim()}
function saveSecret(name,value){const script="$s=ConvertTo-SecureString $env:GA8_SECRET_VALUE -AsPlainText -Force; [IO.File]::WriteAllText($env:GA8_SECRET_FILE,(ConvertFrom-SecureString $s),(New-Object Text.UTF8Encoding($false)))";cp.execFileSync('powershell.exe',['-NoProfile','-Command',script],{env:{...process.env,GA8_SECRET_VALUE:value,GA8_SECRET_FILE:path.join(RUNTIME,'seguridad',name+'.dpapi')},windowsHide:true})}
const admin=readSecret('admin');let app='';
const connections=[],events=[],results=[];let maria,php,active=false;
function sql(query,database=false,user='root',password=admin){const args=['--no-defaults','--protocol=TCP','--host=127.0.0.1','--port='+PORT,'--user='+user,'--batch','--skip-column-names','--default-character-set=utf8mb4','--skip-ssl'];if(database)args.push(DB);const r=cp.spawnSync(path.join(BIN,'mariadb.exe'),args,{env:{...process.env,MYSQL_PWD:password},input:query+'\n',encoding:'utf8',windowsHide:true,timeout:15000});if(r.status!==0)throw Error('Consulta al laboratorio fallida (codigo '+r.status+'); no se divulga SQL o credenciales.');return r.stdout.trim()}
const pause=ms=>new Promise(r=>setTimeout(r,ms));
function listening(port){return new Promise(resolve=>{const s=net.createConnection({host:'127.0.0.1',port});s.setTimeout(400);s.on('connect',()=>{s.destroy();resolve(true)});s.on('error',()=>resolve(false));s.on('timeout',()=>{s.destroy();resolve(false)})})}
async function wait(port,proc){for(let i=0;i<80;i++){if(proc.exitCode!==null)throw Error('Proceso de laboratorio termino durante arranque');if(await listening(port))return;await pause(150)}throw Error('Puerto de laboratorio no disponible')}
function identity(){const x=JSON.parse(sql("SELECT JSON_OBJECT('port',@@port,'data',REPLACE(@@datadir,CHAR(92),'/'),'user',CURRENT_USER(),'version',VERSION())"));assert.equal(Number(x.port),PORT);assert.equal(x.data.replace(/\/$/,'').toLowerCase(),DATA.replaceAll('\\','/').toLowerCase());assert(x.user.startsWith('root@'));return x}
const base='http://127.0.0.1:'+HTTP;
class Client{
 constructor(){this.cookie='';this.csrf=''}
 async request(method,url,body,json=false){assert(url.startsWith('/')&&!url.includes('://'));const headers={Accept:json?'application/json':'text/html'};if(this.cookie)headers.Cookie=this.cookie;if(body!==undefined)headers['Content-Type']=json?'application/json':'application/x-www-form-urlencoded';const r=await fetch(base+url,{method,headers,body:body===undefined?undefined:json?JSON.stringify(body):new URLSearchParams(body).toString(),redirect:'manual',signal:AbortSignal.timeout(10000)});const cookies=r.headers.getSetCookie();if(cookies.length)this.cookie=cookies.map(x=>x.split(';')[0]).join('; ');const text=await r.text();events.push({metodo:method,ruta:url,http:r.status});return{status:r.status,location:r.headers.get('location'),text,json:()=>JSON.parse(text)}}
 async login(user,password){let r=await this.request('GET','/api/session.php',undefined,true);assert.equal(r.status,200);this.csrf=r.json().csrf;assert.equal(typeof this.csrf,'string');r=await this.request('POST','/actions/login_action.php',{usuario:user,password,csrf_token:this.csrf});assert.equal(r.status,302);r=await this.request('GET','/api/session.php',undefined,true);assert.equal(r.json().authenticated,true);this.csrf=r.json().csrf;}
}
function capture(id,objective,expected,observed){results.push({id,objetivo:objective,esperado:expected,obtenido:observed,estado:'APROBADA',fechaUtc:new Date().toISOString()})}
function snap(product){return JSON.parse(sql("SELECT JSON_OBJECT('stock',(SELECT stock_actual FROM productos WHERE id_producto="+product+"),'ventas',(SELECT COUNT(*) FROM ventas),'detalles',(SELECT COUNT(*) FROM detalle_ventas),'movimientos',(SELECT COUNT(*) FROM movimientos_inventario))",true))}
async function main(){
 if(await listening(PORT)||await listening(HTTP))throw Error('Puertos ocupados; no se interviene otro proceso');
 const log=fs.openSync(path.join(RUNTIME,'mariadb/servidor.log'),'a');
 maria=cp.spawn(path.join(BIN,'mariadbd.exe'),['--no-defaults','--console','--basedir='+path.join(RUNTIME,'mariadb/binarios'),'--datadir='+DATA,'--port='+PORT,'--bind-address=127.0.0.1','--skip-name-resolve','--default-time-zone=-05:00','--default-storage-engine=InnoDB','--skip-log-bin','--general-log=0','--slow-query-log=0','--pid-file='+path.join(RUNTIME,'mariadb/servidor.pid')],{cwd:ROOT,windowsHide:true,stdio:['ignore',log,log]});fs.closeSync(log);
 await wait(PORT,maria);active=true;const ident=identity();
 const exists=Number(sql("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='"+DB+"'"));
 if(exists===0){sql('CREATE DATABASE '+DB+' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;');sql(fs.readFileSync(path.join(ROOT,'sql/schema.sql'),'utf8'),true);}
 assert.equal(Number(sql('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE()',true)),14);
 if(fs.existsSync(path.join(RUNTIME,'seguridad/app.dpapi')))app=readSecret('app');
 else{app=crypto.randomBytes(32).toString('hex');saveSecret('app',app);sql("CREATE USER 'ga8_app'@'127.0.0.1' IDENTIFIED BY '"+app+"'; GRANT SELECT, INSERT, UPDATE ON "+DB+".* TO 'ga8_app'@'127.0.0.1';");}
 const appIdent=JSON.parse(sql("SELECT JSON_OBJECT('port',@@port,'data',REPLACE(@@datadir,CHAR(92),'/'),'db',DATABASE(),'user',CURRENT_USER())",true,'ga8_app',app));
 assert.equal(appIdent.db,DB);assert.equal(appIdent.user,'ga8_app@127.0.0.1');assert.equal(Number(appIdent.port),PORT);assert.equal(appIdent.data.replace(/\/$/,'').toLowerCase(),DATA.replaceAll('\\','/').toLowerCase());
 const grants=sql('SHOW GRANTS',true,'ga8_app',app);assert(/SELECT, INSERT, UPDATE/.test(grants));assert(!/ALL PRIVILEGES|GRANT OPTION| ON \*\.\*.*(SELECT|INSERT|UPDATE)/.test(grants));
 const tag=crypto.randomBytes(5).toString('hex'),testPassword=crypto.randomBytes(24).toString('hex');
 const hash=cp.execFileSync(PHP,['-n','-r','echo password_hash(getenv("GA8_TEST_PASSWORD"), PASSWORD_DEFAULT);'],{env:{...process.env,GA8_TEST_PASSWORD:testPassword},encoding:'utf8',windowsHide:true}).trim();assert(/^\$2y\$/.test(hash));
 const owner='ga8_owner_'+tag,employee='ga8_empleada_'+tag;
 sql("INSERT INTO turnos(nombre,hora_inicio,hora_fin) VALUES('Turno ficticio GA8','06:00','14:00');",true);
 const turno=Number(sql('SELECT MAX(id_turno) FROM turnos',true));
 sql("INSERT INTO empleados(nombre,usuario,password_hash,rol,id_turno) VALUES('Dueno ficticio GA8','"+owner+"','"+hash+"','dueño',"+turno+"),('Empleada ficticia GA8','"+employee+"','"+hash+"','empleada',"+turno+");",true);
 sql("INSERT INTO productos(nombre,precio,stock_actual,stock_minimo,activo) VALUES('Pan ficticio GA8 "+tag+"',1250.50,10,5,1),('Inactivo ficticio GA8 "+tag+"',50,10,5,0),('Agotado ficticio GA8 "+tag+"',50,0,5,1);",true);
 const products=sql("SELECT id_producto FROM productos WHERE nombre LIKE '%"+tag+"' ORDER BY id_producto",true).split(/\r?\n/).map(Number);assert.equal(products.length,3);
 const [product,inactive,empty]=products;
 fs.mkdirSync(path.join(RUNTIME,'sesiones'),{recursive:true});
 const phpLog=fs.openSync(path.join(RUNTIME,'php-servidor.log'),'a');
 php=cp.spawn(PHP,['-n','-d','extension_dir='+path.join(path.dirname(PHP),'ext'),'-d','extension=mysqli','-d','extension=mbstring','-d','session.save_path='+path.join(RUNTIME,'sesiones'),'-d','session.name=GA8_DONPAN_SESSID','-d','display_errors=0','-d','log_errors=1','-d','error_log='+path.join(RUNTIME,'php-error.log'),'-S','127.0.0.1:'+HTTP,'-t',ROOT,path.join(ROOT,'scripts/router.php')],{cwd:ROOT,windowsHide:true,env:{...process.env,GA8_DB_PASSWORD:app},stdio:['ignore',phpLog,phpLog]});fs.closeSync(phpLog);
 await wait(HTTP,php);
 const own=new Client(),emp=new Client();await own.login(owner,testPassword);await emp.login(employee,testPassword);
 let r=await own.request('GET','/api/resumen.php',undefined,true);assert.equal(r.status,200);const before=r.json();
 r=await own.request('POST','/actions/inventario_action.php',{id_producto:product,cantidad:5,csrf_token:own.csrf});assert.equal(r.status,302);assert(r.location.includes('ok=1'));assert.equal(snap(product).stock,15);
 assert.equal(Number(sql("SELECT COUNT(*) FROM movimientos_inventario WHERE id_producto="+product+" AND tipo='entrada' AND cantidad=5",true)),1);
 capture('I1','Entrada y movimiento','Stock 10 a 15 y movimiento de 5',{stock:15,movimiento:5});
 r=await emp.request('POST','/actions/venta_action.php',{['cantidad['+product+']']:'2',metodo_pago:'efectivo',csrf_token:emp.csrf});assert.equal(r.status,302);assert(r.location.includes('ok=1'));assert.equal(snap(product).stock,13);
 assert.equal(Number(sql("SELECT COUNT(*) FROM movimientos_inventario WHERE id_producto="+product+" AND tipo='salida' AND cantidad=2",true)),1);
 const sale=Number(sql('SELECT MAX(id_venta) FROM ventas',true));assert.equal(Number(sql('SELECT total FROM ventas WHERE id_venta='+sale,true)),2501);assert.equal(Number(sql('SELECT cantidad FROM detalle_ventas WHERE id_venta='+sale,true)),2);
 capture('I2','Venta transaccional','Stock 13, total 2501 y detalle 2',{stock:13,total:2501,venta:sale});
 const unchanged=snap(product);
 r=await emp.request('POST','/actions/venta_action.php',{['cantidad['+product+']']:'2',['cantidad['+empty+']']:'1',csrf_token:emp.csrf});assert.equal(r.status,302);assert(r.location.includes('error='));assert.deepEqual(snap(product),unchanged);
 capture('I3','Rollback por stock insuficiente','Sin cambios parciales tras procesar una primera linea valida',snap(product));
 r=await emp.request('POST','/actions/venta_action.php',{['cantidad['+inactive+']']:'1',csrf_token:emp.csrf});assert.equal(r.status,302);assert(r.location.includes('error='));assert.deepEqual(snap(product),unchanged);
 capture('I4','Producto inactivo','Rechazo servidor y datos intactos','Rechazado');
 r=await emp.request('POST','/actions/anular_venta_action.php',{id_venta:sale,csrf_token:emp.csrf});assert.equal(r.status,302);assert(!r.location.includes('ok='));assert.deepEqual(snap(product),unchanged);assert.equal(Number(sql('SELECT anulada FROM ventas WHERE id_venta='+sale,true)),0);
 capture('I7','Restriccion por rol','Empleada rechazada; venta vigente y stock 13',{http:302,stock:13});
 for(const token of [undefined,'invalido']){const body={id_producto:product,cantidad:5};if(token!==undefined)body.csrf_token=token;r=await own.request('POST','/actions/inventario_action.php',body);assert.equal(r.status,403);assert.deepEqual(snap(product),unchanged)}
 capture('I8','CSRF ausente y falso','403 y ninguna escritura',{subcasos:2,http:403});
 r=await own.request('GET','/api/resumen.php',undefined,true);const during=r.json();assert.equal(during.ventasHoy,before.ventasHoy+1);
 r=await own.request('POST','/actions/anular_venta_action.php',{id_venta:sale,csrf_token:own.csrf});assert.equal(r.status,302);assert(r.location.includes('ok=anulada'));assert.equal(snap(product).stock,15);
 const audit=JSON.parse(sql('SELECT JSON_OBJECT(\'anulada\',anulada,\'responsable\',anulada_por,\'fecha\',fecha_anulacion) FROM ventas WHERE id_venta='+sale,true));assert.equal(audit.anulada,1);assert(audit.responsable>0&&audit.fecha);
 assert.equal(Number(sql("SELECT COUNT(*) FROM movimientos_inventario WHERE id_producto="+product+" AND tipo='entrada' AND cantidad=2",true)),1);
 capture('I5','Anulacion por dueno','Stock 15, marca, responsable y fecha',{stock:15,auditoria:true});
 const afterCancellation=snap(product);
 r=await own.request('POST','/actions/anular_venta_action.php',{id_venta:sale,csrf_token:own.csrf});assert.equal(r.status,302);assert(r.location.includes('ya_anulada'));assert.deepEqual(snap(product),afterCancellation);
 capture('I6','Anulacion duplicada','Sin segunda restitucion',{stock:15,rechazo:true});
 r=await own.request('GET','/api/resumen.php',undefined,true);const after=r.json();assert.equal(after.ventasHoy,before.ventasHoy);assert.equal(after.totalHoy,before.totalHoy);assert.equal(during.totalHoy,'$2.501');
 for(const u of ['/public/dashboard.php','/public/reportes.php']){r=await own.request('GET',u);assert.equal(r.status,200)}
 capture('I9','Indicadores de ventas vigentes','Incrementan al vender y vuelven a base tras anular',{antes:before.ventasHoy,durante:during.ventasHoy,despues:after.ventasHoy,totalDurante:during.totalHoy});
 r=await own.request('GET','/api/productos.php',undefined,true);assert.equal(r.status,200);const catalog=r.json().productos;assert(catalog.some(p=>p.id_producto===product));assert(!catalog.some(p=>p.id_producto===inactive));
 r=await new Client().request('GET','/api/productos.php',undefined,true);assert.equal(r.status,401);
 capture('I10','Catalogo autenticado y activo','Activos visibles, inactivo excluido, sin sesion 401',{activo:true,inactivoExcluido:true,sinSesion:401});
 const routes=await new Client().request('GET','/.runtime/seguridad/admin.dpapi');assert.equal(routes.status,404);
 const report={fechaUtc:new Date().toISOString(),herramienta:'Node.js fetch y cliente MariaDB local; no PHPUnit ni Postman',integracion:results,solicitudes:events,aislamiento:{port:PORT,http:HTTP,datadirRelativo:'.runtime/mariadb/data',base:DB,usuarioAplicacion:'ga8_app@127.0.0.1',privilegios:['SELECT','INSERT','UPDATE'],version:ident.version},credencialesEnReporte:false,persistencia:'Datos ficticios conservados; no se borra ni reinicializa',limitaciones:['No demuestra capturas visuales ni pruebas de concurrencia.','Redireccion por rol mantiene Location relativo heredado.']};
 fs.writeFileSync(path.join(ROOT,'docs/resultados/integracion.json'),JSON.stringify(report,null,4)+'\n');
 console.log('INTEGRACION APROBADA: '+results.length+' casos, '+events.length+' solicitudes HTTP.');
}
(async()=>{let error;
try{await main()}catch(e){error=e;console.error('FALLO DE INTEGRACION: '+String(e.message).replaceAll(admin,'[REDACTED]').replaceAll(app||'__no_secret__','[REDACTED]'))}
finally{
 if(php&&php.exitCode===null){php.kill();await new Promise(r=>php.once('exit',r))}
 if(active&&maria&&maria.exitCode===null){try{identity();sql('SHUTDOWN;');await new Promise(resolve=>{maria.once('exit',resolve);setTimeout(resolve,15000).unref()})}catch(e){console.error('CIERRE MARIADB REQUIERE REVISION: '+e.message);error=error||e}}
 const free=!(await listening(PORT))&&!(await listening(HTTP));console.log('PUERTOS DEL LABORATORIO LIBRES: '+free);if(!free)error=error||Error('Servidor activo');
 fs.writeFileSync(path.join(ROOT,'docs/resultados/cierre.json'),JSON.stringify({fechaUtc:new Date().toISOString(),puertosLibres:free,phpPid:php?.pid,mariaPid:maria?.pid,integracionAprobada:!error},null,4)+'\n');
 if(error)process.exitCode=1;
}
})();
