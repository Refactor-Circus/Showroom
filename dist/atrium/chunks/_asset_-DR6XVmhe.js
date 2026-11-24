import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { u as c } from "./structure-DbqcM-iO.js";
import { c as ee, o as te, r as l, s as u, t as ne } from "./assets-DNUkY-zI.js";
import { AtriumIntegrationScreen as re, navigateTo as d } from "@refactor-circus/atrium";
import { UButton as ie, UCard as ae, UFormField as oe, UInput as se } from "@refactor-circus/atrium/ui";
import { Fragment as f, computed as p, createCommentVNode as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as ce, isRef as y, openBlock as b, reactive as le, ref as ue, renderList as x, resolveComponent as de, shallowRef as fe, toDisplayString as S, unref as C, useTemplateRef as pe, vModelSelect as me, watch as w, withCtx as T, withDirectives as E, withModifiers as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRoute as k } from "vue-router";
// src/pages/assets/[asset].vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "showroom-asset"
}, j = {
	key: 0,
	class: "space-y-5"
}, M = { class: "flex flex-wrap items-start justify-between gap-3" }, N = {
	class: "text-xl font-semibold",
	"data-testid": "showroom-asset-title"
}, P = { class: "text-sm text-muted" }, F = { class: "flex flex-wrap items-center gap-2" }, I = { class: "grid gap-5 lg:grid-cols-3" }, L = ["src", "alt"], R = { class: "grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 text-sm" }, z = { class: "text-muted" }, he = { class: "font-mono" }, ge = { class: "text-muted" }, _e = { class: "text-muted" }, ve = {
	class: "tabular-nums",
	"data-testid": "showroom-asset-size"
}, ye = { class: "text-muted" }, be = {
	class: "break-all font-mono text-xs",
	"data-testid": "showroom-asset-checksum"
}, xe = { class: "space-y-5 lg:col-span-2" }, Se = { class: "font-semibold" }, Ce = { class: "font-semibold" }, we = { class: "space-y-4" }, B = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "showroom-asset-links-empty"
}, Te = {
	key: 1,
	class: "w-full text-left text-sm"
}, Ee = { class: "text-muted" }, De = { class: "py-2 pr-3 font-medium" }, Oe = { class: "py-2 pr-3 font-medium" }, ke = { class: "py-2 pr-3 font-medium" }, Ae = { class: "divide-y divide-default" }, je = ["data-testid"], Me = { class: "py-2 pr-3" }, Ne = { class: "py-2 pr-3" }, Pe = { class: "py-2 pr-3" }, Fe = { class: "py-2 text-right" }, Ie = ["value"], V = /* @__PURE__ */ ce({
	__name: "[asset]",
	setup(ce) {
		let { t: V, locale: H } = O(), Le = k(), U = i(), W = p(() => String(Le.params.asset)), { client: G, data: K, pending: Re, failed: ze, status: Be, reload: q } = s(async (e) => n(await e.GET("/assets/{asset}", { params: { path: { asset: W.value } } })).data, W), J = p(() => K.value), Y = p(() => J.value?.links ?? []), X = ue(""), Z = fe(null), Q = pe("fileInput"), $ = le({
			type: "product",
			target: "",
			role: "media"
		});
		w(K, () => {
			X.value = J.value?.labels[H.value] ?? "";
		}, { immediate: !0 });
		function Ve(e) {
			Z.value = e.target.files?.[0] ?? null;
		}
		async function He() {
			let e = J.value;
			if (!G || !e) return;
			let t = c(e.labels, H.value, X.value), i = Z.value;
			await U.run(async () => {
				if (!i) return n(await G.PATCH("/assets/{asset}", {
					params: { path: { asset: W.value } },
					body: r({ labels: t })
				}));
				let e = new FormData();
				return e.append("file", i), l(e, t), n(await G.PATCH("/assets/{asset}", {
					params: { path: { asset: W.value } },
					body: e,
					bodySerializer: (e) => e
				}));
			}) && (Z.value = null, Q.value && (Q.value.value = ""), await q());
		}
		async function Ue() {
			if (!G) return;
			let e = a($.role), t = {
				type: $.type,
				target: $.target.trim(),
				...e ? { role: e } : {}
			};
			await U.run(async () => n(await G.POST("/assets/{asset}/links", {
				params: { path: { asset: W.value } },
				body: t
			}))) && ($.target = "", await q());
		}
		async function We(e) {
			if (!G) return;
			let t = {
				type: e.type,
				target: e.target,
				...e.role ? { role: e.role } : {}
			};
			await U.run(async () => n(await G.DELETE("/assets/{asset}/links", { params: {
				path: { asset: W.value },
				query: t
			} }))) && await q();
		}
		async function Ge() {
			G && window.confirm(V("showroom.assets.confirm_delete")) && await U.run(async () => n(await G.DELETE("/assets/{asset}", { params: { path: { asset: W.value } } }))) && await d("/showroom/assets");
		}
		return (n, r) => {
			let i = ie, a = ae, s = se, c = oe, l = de("RouterLink"), d = re;
			return b(), h("div", A, [v(d, {
				"screen-key": "showroom.asset",
				record: C(W)
			}, {
				default: T(() => [v(t, {
					ready: !C(Re) && C(J) !== null,
					failed: C(ze),
					status: C(Be)
				}, {
					default: T(() => [C(J) ? (b(), h("div", j, [
						g("div", M, [g("div", null, [g("h1", N, S(C(o)(C(J).labels, C(H), C(J).code)), 1), g("p", P, S(C(J).filename), 1)]), g("div", F, [v(i, {
							to: C(J).url,
							target: "_blank",
							external: "",
							variant: "outline",
							icon: "i-lucide-external-link",
							"data-testid": "showroom-asset-open"
						}, {
							default: T(() => [_(S(C(V)("showroom.assets.open_file")), 1)]),
							_: 1
						}, 8, ["to"]), v(i, {
							color: "error",
							variant: "outline",
							icon: "i-lucide-trash-2",
							loading: C(U).busy.value,
							"data-testid": "showroom-asset-delete",
							onClick: Ge
						}, {
							default: T(() => [_(S(C(V)("showroom.catalog.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])]),
						v(e, { action: C(U) }, null, 8, ["action"]),
						g("div", I, [v(a, { "data-testid": "showroom-asset-file-card" }, {
							default: T(() => [C(u)(C(J)) ? (b(), h("img", {
								key: 0,
								src: C(J).url,
								alt: C(o)(C(J).labels, C(H), C(J).code),
								class: "mb-3 w-full rounded-md object-contain",
								"data-testid": "showroom-asset-image"
							}, null, 8, L)) : m("", !0), g("dl", R, [
								g("dt", z, S(C(V)("showroom.catalog.fields.code")), 1),
								g("dd", he, S(C(J).code), 1),
								g("dt", ge, S(C(V)("showroom.catalog.fields.type")), 1),
								g("dd", null, S(C(J).mime_type ?? C(V)("showroom.catalog.none")), 1),
								g("dt", _e, S(C(V)("showroom.assets.size")), 1),
								g("dd", ve, S(C(te)(C(J).size)), 1),
								g("dt", ye, S(C(V)("showroom.assets.checksum")), 1),
								g("dd", be, S(C(J).checksum ?? C(V)("showroom.catalog.none")), 1)
							])]),
							_: 1
						}), g("div", xe, [v(a, null, {
							header: T(() => [g("h2", Se, S(C(V)("showroom.assets.details")), 1)]),
							default: T(() => [g("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-asset-form",
								onSubmit: D(He, ["prevent"])
							}, [
								v(c, {
									label: C(V)("showroom.catalog.fields.label_in", { locale: C(H) }),
									error: C(U).error("labels")
								}, {
									default: T(() => [v(s, {
										modelValue: C(X),
										"onUpdate:modelValue": r[0] ||= (e) => y(X) ? X.value = e : null,
										class: "w-64",
										"data-testid": "showroom-asset-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								v(c, {
									label: C(V)("showroom.assets.replace_file"),
									error: C(U).error("file")
								}, {
									default: T(() => [g("input", {
										ref_key: "fileInput",
										ref: Q,
										type: "file",
										class: "text-sm",
										"data-testid": "showroom-asset-file",
										onChange: Ve
									}, null, 544)]),
									_: 1
								}, 8, ["label", "error"]),
								v(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: C(U).busy.value,
									"data-testid": "showroom-asset-save"
								}, {
									default: T(() => [_(S(C(V)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}), v(a, { "data-testid": "showroom-asset-links" }, {
							header: T(() => [g("h2", Ce, S(C(V)("showroom.assets.links")), 1)]),
							default: T(() => [g("div", we, [C(Y).length === 0 ? (b(), h("p", B, S(C(V)("showroom.assets.no_links")), 1)) : (b(), h("table", Te, [g("thead", Ee, [g("tr", null, [
								g("th", De, S(C(V)("showroom.catalog.fields.type")), 1),
								g("th", Oe, S(C(V)("showroom.assets.target")), 1),
								g("th", ke, S(C(V)("showroom.assets.role")), 1),
								r[4] ||= g("th", { class: "py-2" }, null, -1)
							])]), g("tbody", Ae, [(b(!0), h(f, null, x(C(Y), (e) => (b(), h("tr", {
								key: `${e.type}-${e.target}-${e.role ?? ""}`,
								"data-testid": `showroom-asset-link-${e.type}-${e.target}`
							}, [
								g("td", Me, S(C(V)(`showroom.assets.link_types.${e.type}`)), 1),
								g("td", Ne, [v(l, {
									to: C(ee)(e),
									class: "font-mono text-xs hover:underline"
								}, {
									default: T(() => [_(S(e.target), 1)]),
									_: 2
								}, 1032, ["to"])]),
								g("td", Pe, S(e.role ?? C(V)("showroom.catalog.none")), 1),
								g("td", Fe, [v(i, {
									variant: "ghost",
									color: "neutral",
									size: "sm",
									icon: "i-lucide-unlink",
									loading: C(U).busy.value,
									"data-testid": `showroom-asset-unlink-${e.type}-${e.target}`,
									onClick: (t) => We(e)
								}, {
									default: T(() => [_(S(C(V)("showroom.assets.unlink")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])])
							], 8, je))), 128))])])), g("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-asset-link-form",
								onSubmit: D(Ue, ["prevent"])
							}, [
								v(c, {
									label: C(V)("showroom.catalog.fields.type"),
									error: C(U).error("type"),
									required: ""
								}, {
									default: T(() => [E(g("select", {
										"onUpdate:modelValue": r[1] ||= (e) => C($).type = e,
										class: "w-40 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
										"data-testid": "showroom-asset-link-type"
									}, [(b(!0), h(f, null, x(C(ne), (e) => (b(), h("option", {
										key: e,
										value: e
									}, S(C(V)(`showroom.assets.link_types.${e}`)), 9, Ie))), 128))], 512), [[me, C($).type]])]),
									_: 1
								}, 8, ["label", "error"]),
								v(c, {
									label: C(V)("showroom.assets.target"),
									hint: C(V)("showroom.assets.target_hint"),
									error: C(U).error("target"),
									required: ""
								}, {
									default: T(() => [v(s, {
										modelValue: C($).target,
										"onUpdate:modelValue": r[2] ||= (e) => C($).target = e,
										required: "",
										class: "w-48",
										"data-testid": "showroom-asset-link-target"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								v(c, {
									label: C(V)("showroom.assets.role"),
									error: C(U).error("role")
								}, {
									default: T(() => [v(s, {
										modelValue: C($).role,
										"onUpdate:modelValue": r[3] ||= (e) => C($).role = e,
										class: "w-36",
										"data-testid": "showroom-asset-link-role"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								v(i, {
									type: "submit",
									icon: "i-lucide-link",
									loading: C(U).busy.value,
									"data-testid": "showroom-asset-link-submit"
								}, {
									default: T(() => [_(S(C(V)("showroom.assets.link")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)])]),
							_: 1
						})])])
					])) : m("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { V as default };
