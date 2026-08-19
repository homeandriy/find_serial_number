const csrfToken=document.querySelector('meta[name="csrf-token"]')?.content;
const status=document.querySelector('#status'),selectedImage=document.querySelector('#selected-image'),localResult=document.querySelector('#local-result'),aiResult=document.querySelector('#ai-result'),agentSelect=document.querySelector('#agent-select'),aiButton=document.querySelector('#recognize-ai'),refreshButton=document.querySelector('#refresh-folder'),agentForms=document.querySelector('#agent-forms');
let agents=window.initialAiAgents||[],selectedImageId=null;
const providers={openai:'OpenAI',anthropic:'Anthropic (Claude)',gemini:'Google Gemini'};
const request=async(url,method='GET',body)=>{const response=await fetch(url,{method,headers:{Accept:'application/json','X-CSRF-TOKEN':csrfToken,...(body?{'Content-Type':'application/json'}:{})},...(body?{body:JSON.stringify(body)}:{})});const payload=response.status===204?{}:await response.json();if(!response.ok)throw new Error(payload.message||'Не вдалося виконати запит.');return payload};
const escapeHtml=value=>String(value).replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
function renderAgentSelect(){const value=agentSelect.value;agentSelect.innerHTML=agents.length?'<option value="">Виберіть агента</option>'+agents.map(agent=>'<option value="'+agent.id+'">'+escapeHtml(agent.name)+' · '+escapeHtml(agent.model)+'</option>').join(''):'<option value="">Спочатку додайте агента</option>';agentSelect.disabled=!agents.length;if(agents.some(agent=>agent.id===value))agentSelect.value=value;updateAiState()}
function updateAiState(){const ready=Boolean(selectedImageId&&agentSelect.value);aiButton.disabled=!ready;aiResult.disabled=!ready;if(!ready)aiResult.value=agents.length?'Виберіть зображення й AI-агента.':'Додайте AI-агента у налаштуваннях.'}
function agentForm(agent={}){return '<form class="agent-form" data-agent-id="'+(agent.id||'')+'"><div class="agent-form-heading"><strong>'+escapeHtml(agent.name||'Новий AI-агент')+'</strong><button class="delete-agent" type="button" '+(agent.id?'':'hidden')+'>Видалити</button></div><label>Назва (необов’язково)<input name="name" value="'+escapeHtml(agent.name||'')+'" placeholder="Наприклад, OpenAI OCR"></label><label>Постачальник<select name="provider">'+Object.entries(providers).map(([value,label])=>'<option value="'+value+'" '+(agent.provider===value?'selected':'')+'>'+label+'</option>').join('')+'</select></label><label>Модель<input name="model" value="'+escapeHtml(agent.model||'')+'" placeholder="Наприклад, gpt-4.1-mini" required></label><label>API-токен<input name="token" type="password" autocomplete="off" placeholder="'+(agent.id?'Залиште порожнім, щоб не змінювати':'Введіть токен')+'" '+(agent.id?'':'required')+'></label><button class="primary-button" type="submit">Зберегти</button><p class="agent-message"></p></form>'}
function renderForms(){agentForms.innerHTML=agents.map(agent=>agentForm(agent)).join('');bindForms()}
function bindForms(){agentForms.querySelectorAll('.agent-form').forEach(form=>{form.addEventListener('submit',async event=>{event.preventDefault();const message=form.querySelector('.agent-message'),id=form.dataset.agentId,data=Object.fromEntries(new FormData(form));message.textContent='Збереження…';try{const payload=await request(id?'/ai-agents/'+id:'/ai-agents',id?'PUT':'POST',data);if(id)agents=agents.map(agent=>agent.id===id?payload.agent:agent);else agents.push(payload.agent);renderForms();renderAgentSelect()}catch(error){message.textContent=error.message}});form.querySelector('.delete-agent')?.addEventListener('click',async()=>{if(!confirm('Видалити цього AI-агента?'))return;try{await request('/ai-agents/'+form.dataset.agentId,'DELETE');agents=agents.filter(agent=>agent.id!==form.dataset.agentId);renderForms();renderAgentSelect()}catch(error){form.querySelector('.agent-message').textContent=error.message}})})}
document.querySelectorAll('.tab-button').forEach(button=>button.addEventListener('click',()=>{document.querySelectorAll('.tab-button').forEach(item=>item.classList.toggle('is-active',item===button));document.querySelectorAll('.tab-content').forEach(item=>item.classList.toggle('is-active',item.id===button.dataset.tab+'-tab'))}));
document.querySelector('#add-agent')?.addEventListener('click',()=>{agentForms.insertAdjacentHTML('beforeend',agentForm());bindForms();agentForms.lastElementChild?.scrollIntoView({behavior:'smooth',block:'center'})});
refreshButton?.addEventListener('click',()=>{refreshButton.disabled=true;refreshButton.classList.add('is-loading');window.location.reload()});
document.querySelectorAll('.image-card').forEach(card=>card.addEventListener('click',async()=>{document.querySelectorAll('.image-card.active').forEach(item=>item.classList.remove('active'));card.classList.add('active');selectedImageId=card.dataset.imageId;selectedImage.textContent=card.dataset.imageName;status.textContent='Локальне розпізнавання…';localResult.value='Будь ласка, зачекайте.';updateAiState();try{const payload=await request('/images/'+selectedImageId+'/recognize','POST');localResult.value=payload.text||'Текст не знайдено.';status.textContent='Локальний OCR готовий'}catch(error){localResult.value=error.message;status.textContent='Помилка OCR'}}));
agentSelect.addEventListener('change',updateAiState);
aiButton.addEventListener('click',async()=>{aiButton.disabled=true;aiResult.value='AI-розпізнавання…';status.textContent='AI аналізує зображення…';try{const payload=await request('/images/'+selectedImageId+'/recognize-ai/'+agentSelect.value,'POST');aiResult.value=payload.text||'Текст не знайдено.';status.textContent='AI-розпізнавання готове'}catch(error){aiResult.value=error.message;status.textContent='Помилка AI'}finally{updateAiState()}});
renderForms();renderAgentSelect();

const textContextMenu=document.createElement('button');textContextMenu.type='button';textContextMenu.className='text-context-menu';textContextMenu.textContent='Копіювати';textContextMenu.hidden=true;document.body.append(textContextMenu);let contextSelection='';document.addEventListener('contextmenu',event=>{const field=event.target.closest('.result-field'),selected=field?.value.slice(field.selectionStart,field.selectionEnd)||'';if(!selected)return;textContextMenu.hidden=false;textContextMenu.style.left=event.clientX+'px';textContextMenu.style.top=event.clientY+'px';contextSelection=selected;event.preventDefault()});textContextMenu.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(contextSelection);status.textContent='Виділений текст скопійовано'}catch{status.textContent='Не вдалося скопіювати виділений текст'}finally{textContextMenu.hidden=true}});document.addEventListener('click',()=>{textContextMenu.hidden=true});
const deviceTabButton=document.createElement('button');deviceTabButton.className='tab-button';deviceTabButton.textContent='Обладнання';document.querySelector('.tabs').append(deviceTabButton);const devicesTab=document.createElement('section');devicesTab.className='tab-content';devicesTab.innerHTML='<section class="settings-panel"><h2>Обладнання</h2><form id="device-form" class="agent-form"><label>Виділений текст<textarea name="recognized_text" id="device-text" required></textarea></label><label>Модель<select name="device_model_id" id="device-model" required></select></label><label>Дата<input name="registered_at" type="date" value="'+new Date().toISOString().slice(0,10)+'" required></label><button class="primary-button">Зберегти</button></form></section>';document.querySelector('.app-shell').append(devicesTab);deviceTabButton.addEventListener('click',()=>{document.querySelectorAll('.tab-button').forEach(x=>x.classList.toggle('is-active',x===deviceTabButton));document.querySelectorAll('.tab-content').forEach(x=>x.classList.toggle('is-active',x===devicesTab));loadModels()});let models=[];async function loadModels(){const p=await request('/device-models');models=p.models;document.querySelector('#device-model').innerHTML=models.map(m=>'<option value="'+m.id+'">'+m.devices_name+' · '+m.devices_type+' · '+m.device_service+'</option>').join('')||'<option value="">Спочатку додайте модель</option>'}document.querySelector('#device-form').addEventListener('submit',async e=>{e.preventDefault();await request('/devices','POST',Object.fromEntries(new FormData(e.target)));status.textContent='Обладнання збережено'});const addDeviceButton=document.createElement('button');addDeviceButton.type='button';addDeviceButton.className='text-context-menu';addDeviceButton.textContent='Додати в БД';addDeviceButton.hidden=true;document.body.append(addDeviceButton);document.addEventListener('contextmenu',e=>{const f=e.target.closest('.result-field'),v=f?.value.slice(f.selectionStart,f.selectionEnd)||'';if(!v)return;addDeviceButton.hidden=false;addDeviceButton.style.left=e.clientX+'px';addDeviceButton.style.top=(e.clientY+34)+'px';addDeviceButton.dataset.value=v});addDeviceButton.addEventListener('click',()=>{document.querySelector('#device-text').value=addDeviceButton.dataset.value;deviceTabButton.click();addDeviceButton.hidden=true});
const modelTabButton=document.createElement('button');modelTabButton.className='tab-button';modelTabButton.textContent='Моделі';document.querySelector('.tabs').append(modelTabButton);const modelsTab=document.createElement('section');modelsTab.className='tab-content';modelsTab.innerHTML='<section class="settings-panel"><h2>Довідник моделей</h2><form id="model-form" class="agent-form"><label>Назва<input name="devices_name" required></label><label>Тип<select name="devices_type"><option value="tuner">Тюнер</option><option value="modem">Модем</option></select></label><label>Послуга<select name="device_service"><option value="internet">Інтернет</option><option value="television">Телебачення</option></select></label><button class="primary-button">Додати модель</button></form></section>';document.querySelector('.app-shell').append(modelsTab);modelTabButton.addEventListener('click',()=>{document.querySelectorAll('.tab-button').forEach(x=>x.classList.toggle('is-active',x===modelTabButton));document.querySelectorAll('.tab-content').forEach(x=>x.classList.toggle('is-active',x===modelsTab))});document.querySelector('#model-form').addEventListener('submit',async e=>{e.preventDefault();await request('/device-models','POST',Object.fromEntries(new FormData(e.target)));e.target.reset();await loadModels()});
modelsTab.innerHTML='<section class="settings-panel"><div class="settings-heading"><h2>Довідник моделей</h2><button id="new-model" class="primary-button">Додати модель</button></div><div class="filters"><select id="mf-type"><option value="">Всі типи</option><option value="tuner">Тюнер</option><option value="modem">Модем</option></select><select id="mf-service"><option value="">Всі послуги</option><option value="internet">Інтернет</option><option value="television">Телебачення</option></select></div><div id="models-list"></div></section><dialog id="model-dialog"><form method="dialog" id="model-crud" class="agent-form"><h2>Модель</h2><input type="hidden" name="id"><label>Назва<input name="devices_name" required></label><label>Тип<select name="devices_type"><option value="tuner">Тюнер</option><option value="modem">Модем</option></select></label><label>Послуга<select name="device_service"><option value="internet">Інтернет</option><option value="television">Телебачення</option></select></label><button class="primary-button">Зберегти</button><button type="button" class="cancel-dialog">Скасувати</button><p class="agent-message"></p></form></dialog>';
devicesTab.innerHTML='<section class="settings-panel"><h2>Обладнання</h2><form id="device-crud" class="agent-form"><input type="hidden" name="id"><label>Серійники / MAC / текст<textarea name="recognized_text" id="device-text" required></textarea></label><label>Модель<select name="device_model_id" id="device-model" required></select></label><label>Дата<input name="registered_at" type="date" value="'+new Date().toISOString().slice(0,10)+'" required></label><button class="primary-button">Зберегти запис</button><button class="cancel-device" type="button" hidden>Скасувати редагування</button></form><div class="filters"><input id="df-search" placeholder="Серійник або MAC"><input id="df-from" type="date"><input id="df-to" type="date"><select id="df-type"><option value="">Всі типи</option><option value="tuner">Тюнер</option><option value="modem">Модем</option></select><select id="df-service"><option value="">Всі послуги</option><option value="internet">Інтернет</option><option value="television">Телебачення</option></select></div><div id="devices-list"></div></section>';
const label=v=>({tuner:'Тюнер',modem:'Модем',internet:'Інтернет',television:'Телебачення'}[v]||v),qs=s=>document.querySelector(s);
async function showModels(){const p=new URLSearchParams({devices_type:qs('#mf-type').value,device_service:qs('#mf-service').value});const d=await request('/device-models?'+p);qs('#models-list').innerHTML='<table><tr><th>Назва</th><th>Тип</th><th>Послуга</th><th></th></tr>'+d.models.map(m=>'<tr><td>'+escapeHtml(m.devices_name)+'</td><td>'+label(m.devices_type)+'</td><td>'+label(m.device_service)+'</td><td><button data-edit-model="'+m.id+'">Редагувати</button> <button data-delete-model="'+m.id+'">Видалити</button></td></tr>').join('')+'</table>';d.models.forEach(m=>{qs('[data-edit-model="'+m.id+'"]').onclick=()=>openModel(m);qs('[data-delete-model="'+m.id+'"]').onclick=async()=>{if(confirm('Видалити модель?')){await request('/device-models/'+m.id,'DELETE');showModels()}}})}
function openModel(m={}){const f=qs('#model-crud');f.id.value=m.id||'';f.devices_name.value=m.devices_name||'';f.devices_type.value=m.devices_type||'tuner';f.device_service.value=m.device_service||'internet';qs('#model-dialog').showModal()}
qs('#new-model').onclick=()=>openModel();qs('#model-crud').onsubmit=async e=>{e.preventDefault();const f=e.target,d=Object.fromEntries(new FormData(f));try{await request(d.id?'/device-models/'+d.id:'/device-models',d.id?'PUT':'POST',d);qs('#model-dialog').close();showModels();loadModels()}catch(x){f.querySelector('.agent-message').textContent=x.message}};qs('.cancel-dialog').onclick=()=>qs('#model-dialog').close();qs('#mf-type').onchange=showModels;qs('#mf-service').onchange=showModels;
async function showDevices(){const p=new URLSearchParams({search:qs('#df-search').value,date_from:qs('#df-from').value,date_to:qs('#df-to').value,devices_type:qs('#df-type').value,device_service:qs('#df-service').value});const d=await request('/devices?'+p);qs('#devices-list').innerHTML='<table><tr><th>Дата</th><th>Текст</th><th>Модель</th><th>Тип</th><th>Послуга</th><th></th></tr>'+d.devices.map(x=>'<tr><td>'+formatDeviceDate(x.registered_at)+'</td><td>'+escapeHtml(x.recognized_text)+'</td><td>'+escapeHtml(x.devices_name)+'</td><td>'+label(x.devices_type)+'</td><td>'+label(x.device_service)+'</td><td><button data-edit-device="'+x.id+'">Редагувати</button> <button data-delete-device="'+x.id+'">Видалити</button></td></tr>').join('')+'</table>';d.devices.forEach(x=>{qs('[data-edit-device="'+x.id+'"]').onclick=()=>editDevice(x);qs('[data-delete-device="'+x.id+'"]').onclick=async()=>{if(confirm('Видалити запис обладнання?')){await request('/devices/'+x.id,'DELETE');showDevices()}}})}
function editDevice(x){const f=qs('#device-crud');f.id.value=x.id;f.recognized_text.value=x.recognized_text;f.device_model_id.value=x.device_model_id;f.registered_at.value=x.registered_at.slice(0,16);qs('.cancel-device').hidden=false;window.scrollTo({top:0,behavior:'smooth'})}
qs('#device-crud').onsubmit=async e=>{e.preventDefault();const f=e.target,d=Object.fromEntries(new FormData(f));await request(d.id?'/devices/'+d.id:'/devices',d.id?'PUT':'POST',d);f.reset();f.id.value='';qs('.cancel-device').hidden=true;showDevices()};qs('.cancel-device').onclick=()=>{qs('#device-crud').reset();qs('#device-crud').id.value='';qs('.cancel-device').hidden=true};['#df-search','#df-from','#df-to','#df-type','#df-service'].forEach(s=>qs(s).oninput=showDevices);modelTabButton.onclick=()=>{document.querySelectorAll('.tab-button').forEach(x=>x.classList.toggle('is-active',x===modelTabButton));document.querySelectorAll('.tab-content').forEach(x=>x.classList.toggle('is-active',x===modelsTab));showModels()};deviceTabButton.onclick=()=>{document.querySelectorAll('.tab-button').forEach(x=>x.classList.toggle('is-active',x===deviceTabButton));document.querySelectorAll('.tab-content').forEach(x=>x.classList.toggle('is-active',x===devicesTab));loadModels();showDevices()};

const formatDeviceDate=value=>{const d=new Date(value);return String(d.getDate()).padStart(2,'0')+'.'+String(d.getMonth()+1).padStart(2,'0')+'.'+d.getFullYear()+' '+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')+':'+String(d.getSeconds()).padStart(2,'0')};document.querySelector('#device-crud [name=registered_at]').type='datetime-local';document.querySelector('#device-crud [name=registered_at]').value=new Date().toISOString().slice(0,16);
const exportDevicesButton=document.createElement('button');exportDevicesButton.id='export-devices';exportDevicesButton.className='primary-button';exportDevicesButton.type='button';exportDevicesButton.textContent='Вигрузити в Excel';document.querySelector('#devices-list').before(exportDevicesButton);exportDevicesButton.onclick=()=>{const p=new URLSearchParams({search:qs('#df-search').value,date_from:qs('#df-from').value,date_to:qs('#df-to').value,devices_type:qs('#df-type').value,device_service:qs('#df-service').value});window.location='/devices-export?'+p};document.querySelector('.tabs').append(document.querySelector('[data-tab="settings"]'));
exportDevicesButton.onclick=async()=>{const p=new URLSearchParams({search:qs('#df-search').value,date_from:qs('#df-from').value,date_to:qs('#df-to').value,devices_type:qs('#df-type').value,device_service:qs('#df-service').value});const r=await fetch('/devices-export?'+p);const b=await r.blob();const u=URL.createObjectURL(b),a=document.createElement('a');a.href=u;a.download='equipment.csv';a.click();URL.revokeObjectURL(u)};editDevice=async x=>{await loadModels();const f=qs('#device-crud');f.id.value=x.id;f.recognized_text.value=x.recognized_text;f.device_model_id.value=x.device_model_id;f.registered_at.value=x.registered_at.slice(0,16);qs('.cancel-device').hidden=false;window.scrollTo({top:0,behavior:'smooth'})};const editDialog=document.createElement('dialog');editDialog.innerHTML='<form method="dialog" id="device-modal-form" class="agent-form"><h2>Редагування обладнання</h2><input name="id" type="hidden"><label>Текст<textarea name="recognized_text" required></textarea></label><label>Модель<select name="device_model_id" required></select></label><label>Дата і час<input name="registered_at" type="datetime-local" required></label><button class="primary-button">Зберегти</button><button class="close-modal" type="button">Скасувати</button></form>';document.body.append(editDialog);editDevice=async x=>{await loadModels();const f=document.querySelector('#device-modal-form');f.id.value=x.id;f.recognized_text.value=x.recognized_text;f.device_model_id.innerHTML=qs('#device-model').innerHTML;f.device_model_id.value=x.device_model_id;f.registered_at.value=x.registered_at.slice(0,16);editDialog.showModal()};document.querySelector('#device-modal-form').onsubmit=async e=>{e.preventDefault();const d=Object.fromEntries(new FormData(e.target));await request('/devices/'+d.id,'PUT',d);editDialog.close();showDevices()};editDialog.querySelector('.close-modal').onclick=()=>editDialog.close();
// Equipment filters: Kyiv calendar days, validation, and individual clear buttons.
const kyivDateTimeParts = value => {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Europe/Kyiv',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(new Date(value));

    return Object.fromEntries(parts.filter(part => part.type !== 'literal').map(part => [part.type, part.value]));
};
const kyivDateTimeInput = value => {
    const part = kyivDateTimeParts(value);
    return part.year + '-' + part.month + '-' + part.day + 'T' + part.hour + ':' + part.minute;
};
const kyivDisplayDateTime = value => {
    const part = kyivDateTimeParts(value);
    return part.day + '.' + part.month + '.' + part.year + ' ' + part.hour + ':' + part.minute;
};

const deviceFiltersElement = devicesTab.querySelector('.filters');
deviceFiltersElement.innerHTML = '<label class="filter-control">Пошук<input id="df-search" placeholder="Серійник або MAC"><button type="button" class="filter-clear" data-clear-filter="df-search" aria-label="Очистити пошук" hidden>×</button></label><label class="filter-control">Дата від<input id="df-from" type="date"><button type="button" class="filter-clear" data-clear-filter="df-from" aria-label="Очистити дату від" hidden>×</button></label><label class="filter-control">Дата до<input id="df-to" type="date"><button type="button" class="filter-clear" data-clear-filter="df-to" aria-label="Очистити дату до" hidden>×</button></label><label class="filter-control">Тип<select id="df-type"><option value="">Всі типи</option><option value="tuner">Тюнер</option><option value="modem">Модем</option></select><button type="button" class="filter-clear" data-clear-filter="df-type" aria-label="Очистити тип" hidden>×</button></label><label class="filter-control">Послуга<select id="df-service"><option value="">Всі послуги</option><option value="internet">Інтернет</option><option value="television">Телебачення</option></select><button type="button" class="filter-clear" data-clear-filter="df-service" aria-label="Очистити послугу" hidden>×</button></label><p id="device-filter-error" class="filter-error" hidden></p>';

const deviceFilterError = qs('#device-filter-error');
const updateDeviceFilterControls = () => {
    const from = qs('#df-from');
    const to = qs('#df-to');

    from.max = to.value || '';
    to.min = from.value || '';

    document.querySelectorAll('[data-clear-filter]').forEach(button => {
        button.hidden = !qs('#' + button.dataset.clearFilter).value;
    });
};
const deviceQuery = () => {
    const from = qs('#df-from').value;
    const to = qs('#df-to').value;

    if (from && to && to < from) {
        deviceFilterError.textContent = 'Дата «до» не може бути раніше за дату «від».';
        deviceFilterError.hidden = false;
        return null;
    }

    deviceFilterError.hidden = true;

    return new URLSearchParams({
        search: qs('#df-search').value,
        date_from: from,
        date_to: to,
        devices_type: qs('#df-type').value,
        device_service: qs('#df-service').value,
    });
};

showDevices = async () => {
    updateDeviceFilterControls();

    const query = deviceQuery();
    if (!query) {
        return;
    }

    const data = await request('/devices?' + query);
    qs('#devices-list').innerHTML = '<table><tr><th>Дата</th><th>Текст</th><th>Модель</th><th>Тип</th><th>Послуга</th><th></th></tr>' + data.devices.map(device => '<tr><td>' + kyivDisplayDateTime(device.registered_at) + '</td><td>' + escapeHtml(device.recognized_text) + '</td><td>' + escapeHtml(device.devices_name) + '</td><td>' + label(device.devices_type) + '</td><td>' + label(device.device_service) + '</td><td><button data-edit-device="' + device.id + '">Редагувати</button> <button data-delete-device="' + device.id + '">Видалити</button></td></tr>').join('') + '</table>';

    data.devices.forEach(device => {
        qs('[data-edit-device="' + device.id + '"]').onclick = () => editDevice(device);
        qs('[data-delete-device="' + device.id + '"]').onclick = async () => {
            if (confirm('Видалити запис обладнання?')) {
                await request('/devices/' + device.id, 'DELETE');
                showDevices();
            }
        };
    });
};

['#df-search', '#df-from', '#df-to'].forEach(selector => {
    qs(selector).oninput = showDevices;
});
['#df-type', '#df-service'].forEach(selector => {
    qs(selector).onchange = showDevices;
});
document.querySelectorAll('[data-clear-filter]').forEach(button => {
    button.onclick = () => {
        qs('#' + button.dataset.clearFilter).value = '';
        showDevices();
    };
});

exportDevicesButton.onclick = async () => {
    const query = deviceQuery();
    if (!query) {
        return;
    }

    const response = await fetch('/devices-export?' + query);
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = 'equipment.csv';
    anchor.click();
    URL.revokeObjectURL(url);
};

qs('#device-crud [name=registered_at]').value = kyivDateTimeInput(new Date());
editDevice = async device => {
    await loadModels();

    const form = document.querySelector('#device-modal-form');
    form.id.value = device.id;
    form.recognized_text.value = device.recognized_text;
    form.device_model_id.innerHTML = qs('#device-model').innerHTML;
    form.device_model_id.value = device.device_model_id;
    form.registered_at.value = kyivDateTimeInput(device.registered_at);
    editDialog.showModal();
};

// OCR-to-device input modes: normalized serial value or original text.
const normalizeDeviceText = value => value.replace(/[^\p{L}\p{N}]/gu, '');

const addUnformattedDeviceButton = document.createElement('button');
addUnformattedDeviceButton.type = 'button';
addUnformattedDeviceButton.className = 'text-context-menu';
addUnformattedDeviceButton.textContent = 'Додати в БД неформатовано';
addUnformattedDeviceButton.hidden = true;
document.body.append(addUnformattedDeviceButton);

document.addEventListener('contextmenu', event => {
    const field = event.target.closest('.result-field');
    const selection = field?.value.slice(field.selectionStart, field.selectionEnd) || '';

    if (!selection) {
        addUnformattedDeviceButton.hidden = true;
        return;
    }

    addUnformattedDeviceButton.hidden = false;
    addUnformattedDeviceButton.style.left = event.clientX + 'px';
    addUnformattedDeviceButton.style.top = (event.clientY + 68) + 'px';
    addUnformattedDeviceButton.dataset.value = selection;
});

addDeviceButton.addEventListener('click', event => {
    event.stopImmediatePropagation();
    document.querySelector('#device-text').value = normalizeDeviceText(addDeviceButton.dataset.value);
    deviceTabButton.click();
    addDeviceButton.hidden = true;
}, true);

addUnformattedDeviceButton.addEventListener('click', event => {
    event.stopImmediatePropagation();
    document.querySelector('#device-text').value = addUnformattedDeviceButton.dataset.value;
    deviceTabButton.click();
    addUnformattedDeviceButton.hidden = true;
}, true);

document.addEventListener('click', () => {
    addUnformattedDeviceButton.hidden = true;
});

// Compact SVG icons make tabs easier to scan.
const tabIcons = {
    'Розпізнавання': '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h5M4 4v5M20 4h-5M20 4v5M4 20h5M4 20v-5M20 20h-5M20 20v-5M8 12h8M12 8v8"/></svg>',
    'Обладнання': '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h3M8 17h8"/></svg>',
    'Моделі': '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="m4 12 8 4.5 8-4.5M4 16.5l8 4.5 8-4.5"/></svg>',
    'Налаштування AI': '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h9M17 7h3M4 17h3M11 17h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/></svg>',
};
document.querySelectorAll('.tab-button').forEach(button => {
    const title = button.textContent.trim();
    const icon = tabIcons[title];

    if (icon) {
        button.innerHTML = '<span class="tab-icon">' + icon + '</span><span>' + escapeHtml(title) + '</span>';
    }
});

// Explicit close action and reliable click-away closing for OCR context actions.
const closeOcrContextMenuButton = document.createElement('button');
closeOcrContextMenuButton.type = 'button';
closeOcrContextMenuButton.className = 'text-context-menu context-menu-close';
closeOcrContextMenuButton.textContent = 'Закрити';
closeOcrContextMenuButton.hidden = true;
document.body.append(closeOcrContextMenuButton);

const closeOcrContextMenus = () => {
    textContextMenu.hidden = true;
    addDeviceButton.hidden = true;
    addUnformattedDeviceButton.hidden = true;
    closeOcrContextMenuButton.hidden = true;
};

document.addEventListener('contextmenu', event => {
    const field = event.target.closest('.result-field');
    const selection = field?.value.slice(field.selectionStart, field.selectionEnd) || '';

    if (!selection) {
        closeOcrContextMenus();
        return;
    }

    closeOcrContextMenuButton.hidden = false;
    closeOcrContextMenuButton.style.left = event.clientX + 'px';
    closeOcrContextMenuButton.style.top = (event.clientY + 108) + 'px';
});

closeOcrContextMenuButton.addEventListener('click', closeOcrContextMenus);
document.addEventListener('click', event => {
    if (!event.target.closest('.text-context-menu')) {
        closeOcrContextMenus();
    }
}, true);

// Per-photo menu.
const imageActionMenu = document.createElement('div');
imageActionMenu.className = 'image-action-menu';
imageActionMenu.hidden = true;
imageActionMenu.innerHTML = '<button type="button" data-image-action="rotate">Повернути на 90° вправо</button><hr><button type="button" data-image-action="delete" class="danger">Видалити фото</button><hr><button type="button" data-image-action="close" class="danger">Закрити</button>';
document.body.append(imageActionMenu);

document.querySelectorAll('.image-card-menu').forEach(button => {
    button.addEventListener('click', event => {
        event.stopImmediatePropagation();
        const card = button.closest('.image-card');
        imageActionMenu.dataset.imageId = card.dataset.imageId;
        imageActionMenu.hidden = false;
        imageActionMenu.style.left = Math.min(event.clientX, window.innerWidth - 230) + 'px';
        imageActionMenu.style.top = Math.min(event.clientY, window.innerHeight - 170) + 'px';
    }, true);
});

imageActionMenu.addEventListener('click', async event => {
    const action = event.target.dataset.imageAction;
    if (!action || action === 'close') {
        imageActionMenu.hidden = true;
        return;
    }

    const imageId = imageActionMenu.dataset.imageId;
    if (action === 'delete' && !confirm('Видалити це фото без можливості відновлення?')) {
        return;
    }

    try {
        await request('/images/' + imageId + (action === 'rotate' ? '/rotate' : ''), action === 'rotate' ? 'POST' : 'DELETE');
        window.location.reload();
    } catch (error) {
        status.textContent = error.message;
    }
});

document.addEventListener('click', event => {
    if (!event.target.closest('.image-action-menu') && !event.target.closest('.image-card-menu')) {
        imageActionMenu.hidden = true;
    }
}, true);

document.querySelector('#external-homeandriy')?.addEventListener('click', async event => {
    event.preventDefault();
    await request('/website', 'POST');
});

// AI agents: one table and one CRUD dialog instead of unlimited forms.
const settingsTabCrud = document.querySelector('#settings-tab');
settingsTabCrud.innerHTML = '<section class="settings-panel"><div class="settings-heading"><div><h2>AI-агенти</h2><p>API-токен шифрується локально й не показується після збереження.</p></div><button id="new-agent" class="primary-button" type="button">Додати агента</button></div><div id="agents-table"></div></section>';
const agentCrudDialog = document.createElement('dialog');
agentCrudDialog.innerHTML = '<form id="agent-crud" class="agent-form"><h2>AI-агент</h2><input type="hidden" name="id"><label>Назва<input name="name"></label><label>Постачальник<select name="provider"><option value="openai">OpenAI</option><option value="anthropic">Anthropic (Claude)</option><option value="gemini">Google Gemini</option></select></label><label>Модель<input name="model" required></label><label>API-токен<input name="token" type="password" autocomplete="off"></label><button class="primary-button">Зберегти</button><button type="button" class="close-agent-dialog">Скасувати</button><p class="agent-message"></p></form>';
document.body.append(agentCrudDialog);
const agentTable = qs('#agents-table');
const openAgentCrud = agent => {
    const form = qs('#agent-crud');
    form.reset();
    form.id.value = agent?.id || '';
    form.name.value = agent?.name || '';
    form.provider.value = agent?.provider || 'openai';
    form.model.value = agent?.model || '';
    form.token.required = !agent;
    form.token.placeholder = agent ? 'Залиште порожнім, щоб не змінювати' : 'Введіть токен';
    agentCrudDialog.showModal();
};
const renderAgentTable = () => {
    agentTable.innerHTML = '<table><tr><th>Назва</th><th>Постачальник</th><th>Модель</th><th></th></tr>' + agents.map(agent => '<tr><td>' + escapeHtml(agent.name) + '</td><td>' + escapeHtml(providers[agent.provider]) + '</td><td>' + escapeHtml(agent.model) + '</td><td><button data-edit-agent="' + agent.id + '">Редагувати</button> <button data-delete-agent="' + agent.id + '" class="danger-button">Видалити</button></td></tr>').join('') + '</table>';
    agents.forEach(agent => {
        qs('[data-edit-agent="' + agent.id + '"]').onclick = () => openAgentCrud(agent);
        qs('[data-delete-agent="' + agent.id + '"]').onclick = async () => {
            if (confirm('Видалити AI-агента?')) {
                await request('/ai-agents/' + agent.id, 'DELETE');
                agents = agents.filter(item => item.id !== agent.id);
                renderAgentTable();
                renderAgentSelect();
            }
        };
    });
};
qs('#new-agent').onclick = () => openAgentCrud();
qs('#agent-crud').onsubmit = async event => {
    event.preventDefault();
    const form = event.target;
    const data = Object.fromEntries(new FormData(form));
    try {
        const response = await request(data.id ? '/ai-agents/' + data.id : '/ai-agents', data.id ? 'PUT' : 'POST', data);
        agents = data.id ? agents.map(agent => agent.id === data.id ? response.agent : agent) : [...agents, response.agent];
        agentCrudDialog.close();
        renderAgentTable();
        renderAgentSelect();
    } catch (error) { form.querySelector('.agent-message').textContent = error.message; }
};
qs('.close-agent-dialog').onclick = () => agentCrudDialog.close();
renderAgentTable();

// Unified OCR context menu with icons, separators, and reliable dismissal.
const unifiedOcrMenu = document.createElement('div');
unifiedOcrMenu.className = 'context-action-menu';
unifiedOcrMenu.hidden = true;
unifiedOcrMenu.innerHTML = '<button type="button" data-ocr-action="copy"><span>⧉</span>Копіювати</button><button type="button" data-ocr-action="normal"><span>⌁</span>Додати в БД</button><button type="button" data-ocr-action="raw"><span>≡</span>Додати в БД неформатовано</button><hr><button type="button" data-ocr-action="close" class="danger"><span>×</span>Закрити</button>';
document.body.append(unifiedOcrMenu);

const hideAllContextActions = () => {
    unifiedOcrMenu.hidden = true;
    imageActionMenu.hidden = true;
    closeOcrContextMenus();
};

document.addEventListener('contextmenu', event => {
    const field = event.target.closest('.result-field');
    const selection = field?.value.slice(field.selectionStart, field.selectionEnd) || '';

    if (!selection) {
        return;
    }

    event.preventDefault();
    contextSelection = selection;
    unifiedOcrMenu.hidden = false;
    unifiedOcrMenu.style.left = Math.min(event.clientX, window.innerWidth - 270) + 'px';
    unifiedOcrMenu.style.top = Math.min(event.clientY, window.innerHeight - 170) + 'px';
    textContextMenu.hidden = true;
    addDeviceButton.hidden = true;
    addUnformattedDeviceButton.hidden = true;
    closeOcrContextMenuButton.hidden = true;
});

unifiedOcrMenu.addEventListener('click', async event => {
    const action = event.target.closest('[data-ocr-action]')?.dataset.ocrAction;

    if (!action || action === 'close') {
        hideAllContextActions();
        return;
    }

    if (action === 'copy') {
        try { await navigator.clipboard.writeText(contextSelection); } catch { status.textContent = 'Не вдалося скопіювати виділений текст'; }
    } else {
        qs('#device-text').value = action === 'normal' ? normalizeDeviceText(contextSelection) : contextSelection;
        deviceTabButton.click();
    }

    hideAllContextActions();
});

document.addEventListener('pointerdown', event => {
    if (!event.target.closest('.context-action-menu') && !event.target.closest('.image-action-menu') && !event.target.closest('.image-card-menu')) {
        hideAllContextActions();
    }
}, true);

imageActionMenu.addEventListener('click', async event => {
    const action = event.target.closest('[data-image-action]')?.dataset.imageAction;

    if (action !== 'rotate') {
        return;
    }

    event.stopImmediatePropagation();
    const imageId = imageActionMenu.dataset.imageId;

    try {
        await request('/images/' + imageId + '/rotate', 'POST');
        const image = document.querySelector('.image-card[data-image-id="' + imageId + '"] img');
        if (image) image.src = '/images/' + imageId + '?v=' + Date.now();
        imageActionMenu.hidden = true;
    } catch (error) {
        status.textContent = error.message;
    }
}, true);
imageActionMenu.innerHTML = '<button type="button" data-image-action="rotate"><span>↻</span>Повернути на 90° вправо</button><hr><button type="button" data-image-action="delete" class="danger"><span>⌫</span>Видалити фото</button><hr><button type="button" data-image-action="close" class="danger"><span>×</span>Закрити</button>';