const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=["./html2pdf-DPj86RUe.js","./vendor-CcMVubkO.js","./app-DzWA8JdN.js","./ui-44uVE41x.js","./app-DrSxWhii.css"])))=>i.map(i=>d[i]);
import{_ as g}from"./app-DzWA8JdN.js";import{j as i}from"./ui-44uVE41x.js";import{B as x}from"./button-DJ6EGOAn.js";import{a as f,b,c as h}from"./tooltip-C28rtVxx.js";import{f as v,a as r,m as p}from"./helpers-BRfeGxMF.js";import{u as _}from"./useTranslation-DtVDvPEq.js";import{D as y}from"./download-DpWzK9fg.js";import"./vendor-CcMVubkO.js";import"./index-DnPxjgVG.js";import"./utils-BMVe_KuB.js";import"./utils-CnJ_TjZZ.js";import"./index-DtHwtYlq.js";import"./createLucideIcon-Dov4NOxw.js";function F({contract:e,variant:s="outline",size:a="sm"}){const{t}=_(),d=()=>{var n;const l=document.createElement("div");l.innerHTML=`
            <div style="padding: 40px; font-family: Arial, sans-serif;">
                <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 24px; margin-bottom: 32px;">
                    <h1 style="font-size: 24px; font-weight: bold; color: #111827; margin-bottom: 8px;">${e.subject}</h1>
                    <p style="color: #6b7280;">${e.contract_number}</p>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">${t("Contract Details")}</h3>
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t("Client")}</label>
                            <p style="font-weight: 500; color: #111827;">${((n=e.user)==null?void 0:n.name)||t("Not Assigned")}</p>
                        </div>
                        <div>
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t("Contract Value")}</label>
                            <p style="font-size: 18px; font-weight: 600; color: #111827;">${e.value?v(e.value):t("Not Set")}</p>
                        </div>
                    </div>
                    <div>
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">${t("Timeline")}</h3>
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t("Start Date")}</label>
                            <p style="font-weight: 500; color: #111827;">${e.start_date?r(e.start_date):t("Not Set")}</p>
                        </div>
                        <div>
                            <label style="font-size: 14px; font-weight: 500; color: #6b7280;">${t("End Date")}</label>
                            <p style="font-weight: 500; color: #111827;">${e.end_date?r(e.end_date):t("Not Set")}</p>
                        </div>
                    </div>
                </div>
                ${e.description?`
                    <div style="margin-bottom: 32px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 16px;">${t("Terms and Conditions")}</h3>
                        <div style="border-left: 4px solid #d1d5db; padding-left: 16px;">
                            <p style="color: #374151; line-height: 1.6; white-space: pre-wrap;">${e.description}</p>
                        </div>
                    </div>
                `:""}
                <div style="border-top: 1px solid #e5e7eb; padding-top: 32px;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin-bottom: 24px;">${t("Signatures")}</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px;">
                        ${e.signatures&&e.signatures.length>0?e.signatures.map(o=>`
                                <div style="text-align: center;">
                                    <div style="border-bottom: 2px solid #9ca3af; padding-bottom: 8px; margin-bottom: 12px; height: 64px; display: flex; align-items: end; justify-content: center;">
                                        <img src="${o.signature_data}" alt="Signature" style="max-height: 48px; max-width: 100%; object-fit: contain;" />
                                    </div>
                                    <p style="font-weight: 500; color: #111827;">${o.user.name}</p>
                                    <p style="font-size: 14px; color: #6b7280;">${p(o.signed_at)}</p>
                                </div>
                            `).join(""):`<div style="text-align: center;">
                                <div style="border-bottom: 2px solid #d1d5db; padding-bottom: 8px; margin-bottom: 12px; height: 64px;"></div>
                                <p style="font-weight: 500; color: #111827;">${t("Client Signature")}</p>
                                <p style="font-size: 14px; color: #6b7280;">${t("Date")}: _______________</p>
                            </div>
                            <div style="text-align: center;">
                                <div style="border-bottom: 2px solid #d1d5db; padding-bottom: 8px; margin-bottom: 12px; height: 64px;"></div>
                                <p style="font-weight: 500; color: #111827;">${t("Company Representative")}</p>
                                <p style="font-size: 14px; color: #6b7280;">${t("Date")}: _______________</p>
                            </div>`}
                    </div>
                </div>
                <div style="text-align: center; font-size: 12px; color: #9ca3af; padding-top: 32px; margin-top: 32px; border-top: 1px solid #e5e7eb;">
                    <p>${t("Generated on")} ${p(e.created_at)} • ${e.contract_number}</p>
                </div>
            </div>
        `;const m={margin:.5,filename:`contract-${e.contract_number}.pdf`,image:{type:"jpeg",quality:.98},html2canvas:{scale:2},jsPDF:{unit:"in",format:"letter",orientation:"portrait"}};g(()=>import("./html2pdf-DPj86RUe.js").then(o=>o.h),__vite__mapDeps([0,1,2,3,4]),import.meta.url).then(o=>{o.default().set(m).from(l).save()})};return i.jsxs(f,{children:[i.jsx(b,{asChild:!0,children:i.jsx(x,{variant:s,size:a,onClick:d,children:i.jsx(y,{className:"h-4 w-4"})})}),i.jsx(h,{children:i.jsx("p",{children:t("Download PDF")})})]})}export{F as default};
