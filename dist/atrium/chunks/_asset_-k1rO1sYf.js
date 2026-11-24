import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { u as c } from "./structure-DbqcM-iO.js";
import { c as ee, o as l, r as u, s as te, t as d } from "./assets-DNUkY-zI.js";
import { AtriumDetails as f, AtriumIntegrationScreen as ne, AtriumPageHeader as re, AtriumState as ie, navigateTo as p } from "@refactor-circus/atrium";
import { UButton as ae, UCard as oe, UFormField as se, UInput as ce, ULink as le, USelect as m, UTable as ue } from "@refactor-circus/atrium/ui";
import { computed as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, isRef as de, openBlock as S, reactive as C, ref as w, shallowRef as fe, toDisplayString as T, unref as E, useTemplateRef as D, watch as O, withCtx as k, withModifiers as A } from "vue";
import { useI18n as j } from "vue-i18n";
import { useRoute as M } from "vue-router";
// src/pages/assets/[asset].vue?vue&type=script&setup=true&lang.ts
var N = {
	class: "space-y-4",
	"data-testid": "showroom-asset"
}, P = {
	key: 0,
	class: "space-y-5"
}, F = {
	class: "truncate",
	"data-testid": "showroom-asset-title"
}, I = { class: "grid gap-5 lg:grid-cols-3" }, L = ["src", "alt"], R = { class: "space-y-5 lg:col-span-2" }, z = { class: "font-semibold" }, B = { class: "font-semibold" }, V = { class: "space-y-4" }, pe = ["data-testid"], H = /* @__PURE__ */ x({
	__name: "[asset]",
	setup(x) {
		let { t: H, locale: U } = j(), me = M(), W = i(), G = h(() => String(me.params.asset)), { client: K, data: q, pending: he, failed: ge, status: _e, reload: J } = s(async (e) => n(await e.GET("/assets/{asset}", { params: { path: { asset: G.value } } })).data, G), Y = h(() => q.value), ve = h(() => Y.value?.links ?? []), X = w(""), Z = fe(null), Q = D("fileInput"), ye = h(() => d.map((e) => ({
			label: H(`showroom.assets.link_types.${e}`),
			value: e
		}))), be = h(() => [
			{
				accessorKey: "type",
				header: H("showroom.catalog.fields.type")
			},
			{
				accessorKey: "target",
				header: H("showroom.assets.target")
			},
			{
				accessorKey: "role",
				header: H("showroom.assets.role")
			},
			{
				id: "actions",
				header: "",
				meta: { class: { td: "text-right" } }
			}
		]), $ = C({
			type: "product",
			target: "",
			role: "media"
		});
		O(q, () => {
			X.value = Y.value?.labels[U.value] ?? "";
		}, { immediate: !0 });
		function xe(e) {
			Z.value = e.target.files?.[0] ?? null;
		}
		async function Se() {
			let e = Y.value;
			if (!K || !e) return;
			let t = c(e.labels, U.value, X.value), i = Z.value;
			await W.run(async () => {
				if (!i) return n(await K.PATCH("/assets/{asset}", {
					params: { path: { asset: G.value } },
					body: r({ labels: t })
				}));
				let e = new FormData();
				return e.append("file", i), u(e, t), n(await K.PATCH("/assets/{asset}", {
					params: { path: { asset: G.value } },
					body: e,
					bodySerializer: (e) => e
				}));
			}) && (Z.value = null, Q.value?.inputRef && (Q.value.inputRef.value = ""), await J());
		}
		async function Ce() {
			if (!K) return;
			let e = a($.role), t = {
				type: $.type,
				target: $.target.trim(),
				...e ? { role: e } : {}
			};
			await W.run(async () => n(await K.POST("/assets/{asset}/links", {
				params: { path: { asset: G.value } },
				body: t
			}))) && ($.target = "", await J());
		}
		async function we(e) {
			if (!K) return;
			let t = {
				type: e.type,
				target: e.target,
				...e.role ? { role: e.role } : {}
			};
			await W.run(async () => n(await K.DELETE("/assets/{asset}/links", { params: {
				path: { asset: G.value },
				query: t
			} }))) && await J();
		}
		async function Te() {
			K && window.confirm(H("showroom.assets.confirm_delete")) && await W.run(async () => n(await K.DELETE("/assets/{asset}", { params: { path: { asset: G.value } } }))) && await p("/showroom/assets");
		}
		return (n, r) => {
			let i = ae, a = re, s = f, c = oe, u = ce, d = se, p = ie, h = le, x = ue, C = m, w = ne;
			return S(), _("div", N, [b(w, {
				"screen-key": "showroom.asset",
				record: E(G)
			}, {
				default: k(() => [b(t, {
					ready: !E(he) && E(Y) !== null,
					failed: E(ge),
					status: E(_e)
				}, {
					default: k(() => [E(Y) ? (S(), _("div", P, [
						b(a, {
							overline: E(H)("showroom.assets.title"),
							description: E(Y).filename
						}, {
							title: k(() => [v("span", F, T(E(o)(E(Y).labels, E(U), E(Y).code)), 1)]),
							default: k(() => [b(i, {
								to: E(Y).url,
								target: "_blank",
								external: "",
								variant: "outline",
								icon: "i-lucide-external-link",
								"data-testid": "showroom-asset-open"
							}, {
								default: k(() => [y(T(E(H)("showroom.assets.open_file")), 1)]),
								_: 1
							}, 8, ["to"]), b(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: E(W).busy.value,
								"data-testid": "showroom-asset-delete",
								onClick: Te
							}, {
								default: k(() => [y(T(E(H)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["overline", "description"]),
						b(e, { action: E(W) }, null, 8, ["action"]),
						v("div", I, [b(c, { "data-testid": "showroom-asset-file-card" }, {
							default: k(() => [E(te)(E(Y)) ? (S(), _("img", {
								key: 0,
								src: E(Y).url,
								alt: E(o)(E(Y).labels, E(U), E(Y).code),
								class: "mb-3 w-full rounded-md object-contain",
								"data-testid": "showroom-asset-image"
							}, null, 8, L)) : g("", !0), b(s, {
								items: [
									{
										key: "code",
										label: E(H)("showroom.catalog.fields.code"),
										value: E(Y).code,
										mono: !0
									},
									{
										key: "type",
										label: E(H)("showroom.catalog.fields.type"),
										value: E(Y).mime_type
									},
									{
										key: "size",
										label: E(H)("showroom.assets.size"),
										value: E(l)(E(Y).size),
										testid: "showroom-asset-size"
									},
									{
										key: "checksum",
										label: E(H)("showroom.assets.checksum"),
										value: E(Y).checksum,
										mono: !0,
										testid: "showroom-asset-checksum"
									}
								],
								empty: E(H)("showroom.catalog.none")
							}, null, 8, ["items", "empty"])]),
							_: 1
						}), v("div", R, [b(c, null, {
							header: k(() => [v("h2", z, T(E(H)("showroom.assets.details")), 1)]),
							default: k(() => [v("form", {
								class: "flex flex-wrap items-start gap-3",
								"data-testid": "showroom-asset-form",
								onSubmit: A(Se, ["prevent"])
							}, [
								b(d, {
									label: E(H)("showroom.catalog.fields.label_in", { locale: E(U) }),
									error: E(W).error("labels")
								}, {
									default: k(() => [b(u, {
										modelValue: E(X),
										"onUpdate:modelValue": r[0] ||= (e) => de(X) ? X.value = e : null,
										class: "w-64",
										"data-testid": "showroom-asset-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(d, {
									label: E(H)("showroom.assets.replace_file"),
									error: E(W).error("file")
								}, {
									default: k(() => [b(u, {
										ref_key: "fileInput",
										ref: Q,
										type: "file",
										"data-testid": "showroom-asset-file",
										onChange: xe
									}, null, 512)]),
									_: 1
								}, 8, ["label", "error"]),
								b(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: E(W).busy.value,
									class: "mt-field-label",
									"data-testid": "showroom-asset-save"
								}, {
									default: k(() => [y(T(E(H)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}), b(c, { "data-testid": "showroom-asset-links" }, {
							header: k(() => [v("h2", B, T(E(H)("showroom.assets.links")), 1)]),
							default: k(() => [v("div", V, [b(x, {
								data: E(ve),
								columns: E(be)
							}, {
								empty: k(() => [b(p, {
									compact: "",
									title: E(H)("showroom.assets.no_links"),
									"data-testid": "showroom-asset-links-empty"
								}, null, 8, ["title"])]),
								"type-cell": k(({ row: { original: e } }) => [v("span", { "data-testid": `showroom-asset-link-${e.type}-${e.target}` }, T(E(H)(`showroom.assets.link_types.${e.type}`)), 9, pe)]),
								"target-cell": k(({ row: { original: e } }) => [b(h, {
									to: E(ee)(e),
									class: "font-mono text-xs hover:underline"
								}, {
									default: k(() => [y(T(e.target), 1)]),
									_: 2
								}, 1032, ["to"])]),
								"role-cell": k(({ row: { original: e } }) => [y(T(e.role ?? E(H)("showroom.catalog.none")), 1)]),
								"actions-cell": k(({ row: { original: e } }) => [b(i, {
									variant: "ghost",
									color: "neutral",
									size: "sm",
									icon: "i-lucide-unlink",
									loading: E(W).busy.value,
									"data-testid": `showroom-asset-unlink-${e.type}-${e.target}`,
									onClick: (t) => we(e)
								}, {
									default: k(() => [y(T(E(H)("showroom.assets.unlink")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])]),
								_: 1
							}, 8, ["data", "columns"]), v("form", {
								class: "flex flex-wrap items-start gap-3",
								"data-testid": "showroom-asset-link-form",
								onSubmit: A(Ce, ["prevent"])
							}, [
								b(d, {
									label: E(H)("showroom.catalog.fields.type"),
									error: E(W).error("type"),
									required: ""
								}, {
									default: k(() => [b(C, {
										modelValue: E($).type,
										"onUpdate:modelValue": r[1] ||= (e) => E($).type = e,
										items: E(ye),
										class: "w-40",
										"data-testid": "showroom-asset-link-type"
									}, null, 8, ["modelValue", "items"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(d, {
									label: E(H)("showroom.assets.target"),
									hint: E(H)("showroom.assets.target_hint"),
									error: E(W).error("target"),
									required: ""
								}, {
									default: k(() => [b(u, {
										modelValue: E($).target,
										"onUpdate:modelValue": r[2] ||= (e) => E($).target = e,
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
								b(d, {
									label: E(H)("showroom.assets.role"),
									error: E(W).error("role")
								}, {
									default: k(() => [b(u, {
										modelValue: E($).role,
										"onUpdate:modelValue": r[3] ||= (e) => E($).role = e,
										class: "w-36",
										"data-testid": "showroom-asset-link-role"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(i, {
									type: "submit",
									icon: "i-lucide-link",
									loading: E(W).busy.value,
									class: "mt-field-label",
									"data-testid": "showroom-asset-link-submit"
								}, {
									default: k(() => [y(T(E(H)("showroom.assets.link")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)])]),
							_: 1
						})])])
					])) : g("", !0)]),
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
export { H as default };
