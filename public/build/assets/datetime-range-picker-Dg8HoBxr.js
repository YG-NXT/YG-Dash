import{j as r}from"./ui-44uVE41x.js";import{r as u}from"./vendor-CcMVubkO.js";import{D as b}from"./react-datepicker--cGWJYit.js";import{c as _}from"./utils-CnJ_TjZZ.js";import{B as M}from"./button-DJ6EGOAn.js";import{P as E,a as H,b as z}from"./popover-C4wEPUu9.js";import{u as I}from"./useTranslation-WQoFQdXM.js";import{C as L}from"./calendar-DvmJ2XsD.js";function G({value:a,onChange:d,placeholder:S,className:w,id:U,required:B,timeFormat:h="HH:mm",dateFormat:f="MMM d, yyyy h:mm aa",mode:i="range"}){const{t:p}=I(),[k,x]=u.useState(!1),[o,m]=u.useState(null),[c,g]=u.useState(null);u.useEffect(()=>{if(a&&(a.includes("T")||a.includes("Z"))){const e=D(a);if(e!==a){d(e);return}}if(a)if(i==="single")m(new Date(a.replace(" ","T"))),g(null);else{const[e,n]=a.split(" - ");m(e?new Date(e.replace(" ","T")):null),g(n?new Date(n.replace(" ","T")):null)}else m(null),g(null)},[a,i]);const D=e=>{const n=t=>{if(!t)return"";if(t.includes("Z")){const s=new Date(t),$=s.getFullYear(),T=String(s.getMonth()+1).padStart(2,"0"),N=String(s.getDate()).padStart(2,"0"),C=String(s.getHours()).padStart(2,"0"),P=String(s.getMinutes()).padStart(2,"0");return`${$}-${T}-${N} ${C}:${P}`}return t};if(i==="single")return n(e);{const[t,s]=e.split(" - ");return`${n(t)} - ${n(s)}`}},y=(e,n)=>{const t={year:"numeric",month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"};return i==="single"?e?e.toLocaleDateString("en-US",t):"":!e||!n?"":`${e.toLocaleDateString("en-US",t)} - ${n.toLocaleDateString("en-US",t)}`},l=e=>{const n=`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}-${String(e.getDate()).padStart(2,"0")}`,t=`${String(e.getHours()).padStart(2,"0")}:${String(e.getMinutes()).padStart(2,"0")}`;return`${n} ${t}`},v=e=>{e&&(m(e),i==="single"?(d(l(e)),x(!1)):d(c?`${l(e)} - ${l(c)}`:`${l(e)} - `))},j=e=>{e&&o&&(g(e),d(`${l(o)} - ${l(e)}`),x(!1))};return r.jsxs("div",{className:_("w-full",w),onWheel:e=>{k||e.stopPropagation()},children:[r.jsxs(E,{open:k,onOpenChange:x,modal:!1,children:[r.jsx(H,{asChild:!0,children:r.jsxs(M,{variant:"outline",className:_("w-full justify-start text-left font-normal h-10",!a&&"text-muted-foreground"),children:[r.jsx(L,{className:"mr-2 h-4 w-4"}),a&&o&&(i==="single"||c)?y(o,c):S||p(i==="single"?"Select date time":"Select date time range")]})}),r.jsx(z,{className:"w-auto p-0",align:"start",onWheel:e=>{e.stopPropagation()},children:r.jsx("div",{className:"datetime-range-wrapper",children:i==="single"?r.jsxs("div",{className:"p-3",children:[r.jsx("div",{className:"text-sm font-medium mb-2 text-center",children:p("Select Date & Time")}),r.jsx(b,{selected:o,onChange:v,showTimeSelect:!0,timeFormat:h,timeIntervals:15,timeCaption:"Time",dateFormat:f,inline:!0})]}):r.jsxs("div",{className:"flex",children:[r.jsxs("div",{className:"p-3 border-r border-border",children:[r.jsx("div",{className:"text-sm font-medium mb-2 text-center",children:p("Start Date & Time")}),r.jsx(b,{selected:o,onChange:v,showTimeSelect:!0,timeFormat:h,timeIntervals:15,timeCaption:"Time",dateFormat:f,inline:!0,maxDate:c||void 0})]}),r.jsxs("div",{className:"p-3",children:[r.jsx("div",{className:"text-sm font-medium mb-2 text-center",children:p("End Date & Time")}),r.jsx(b,{selected:c,onChange:j,showTimeSelect:!0,timeFormat:h,timeIntervals:15,timeCaption:"Time",dateFormat:f,inline:!0,minDate:o||void 0})]})]})})})]}),r.jsx("style",{children:`
        .datetime-range-wrapper .react-datepicker {
          font-family: inherit;
          border: none;
          background: hsl(var(--background));
          color: hsl(var(--foreground));
        }
        .datetime-range-wrapper .react-datepicker__header {
          background: hsl(var(--background));
          border-bottom: 1px solid hsl(var(--border));
          border-radius: 0;
        }
        .datetime-range-wrapper .react-datepicker__current-month,
        .datetime-range-wrapper .react-datepicker__day-name {
          color: hsl(var(--foreground));
          font-weight: 500;
        }
        .datetime-range-wrapper .react-datepicker__day {
          color: hsl(var(--foreground));
          border-radius: 6px;
        }
        .datetime-range-wrapper .react-datepicker__day:hover {
          background: hsl(var(--accent));
          color: hsl(var(--accent-foreground));
        }
        .datetime-range-wrapper .react-datepicker__day--selected {
          background: hsl(var(--primary));
          color: hsl(var(--primary-foreground));
        }
        .datetime-range-wrapper .react-datepicker__navigation {
          border: none;
          border-radius: 6px;
        }
        .datetime-range-wrapper .react-datepicker__navigation:hover {
          background: hsl(var(--accent));
        }
        .datetime-range-wrapper .react-datepicker__navigation-icon::before {
          border-color: hsl(var(--foreground));
        }
        .datetime-range-wrapper .react-datepicker__day--outside-month {
          color: hsl(var(--muted-foreground));
        }
        .datetime-range-wrapper .react-datepicker__day--disabled {
          color: hsl(var(--muted-foreground));
          opacity: 0.5;
        }
        .datetime-range-wrapper .react-datepicker__time-container {
          background: hsl(var(--background));
          border-left: 1px solid hsl(var(--border));
        }
        .datetime-range-wrapper .react-datepicker__time {
          background: hsl(var(--background));
        }
        .datetime-range-wrapper .react-datepicker__time-box {
          background: hsl(var(--background));
        }
        .datetime-range-wrapper .react-datepicker__time-list-item {
          color: hsl(var(--foreground));
        }
        .datetime-range-wrapper .react-datepicker__time-list-item:hover {
          background: hsl(var(--accent));
        }
        .datetime-range-wrapper .react-datepicker__time-list-item--selected {
          background: hsl(var(--primary));
          color: hsl(var(--primary-foreground));
        }
        .datetime-range-wrapper .react-datepicker__time-name {
          color: hsl(var(--foreground));
        }
      `})]})}export{G as D};
